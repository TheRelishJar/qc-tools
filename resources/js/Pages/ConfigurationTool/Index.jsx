import React, { useState, useEffect } from 'react';
import { Head, useForm } from '@inertiajs/react';

export default function Index({ industries, purityLevels }) {
    const [applications, setApplications] = useState([]);
    const [selectedApplication, setSelectedApplication] = useState(null);
    const [presetOpen, setPresetOpen] = useState(false);
    const [highlightedFields, setHighlightedFields] = useState({
        particulate: false,
        water: false,
        oil: false
    });
    
    // Debug: Log purity levels on mount
    useEffect(() => {
        console.log('Purity Levels:', purityLevels);
        console.log('Sample particle description:', purityLevels.particle[1]);
    }, []);
    
    const { data, setData, post, processing, errors } = useForm({
        particulate_class: '',
        water_class: '',
        oil_class: '',
        flow: '',
        preset_industry_id: '',
        preset_application_id: '',
    });

    // Fetch applications when industry changes
    useEffect(() => {
        if (data.preset_industry_id) {
            fetch(`/configuration/applications/${data.preset_industry_id}`)
                .then(res => res.json())
                .then(result => {
                    setApplications(result.applications);
                    setData('preset_application_id', '');
                    setSelectedApplication(null);
                });
        } else {
            setApplications([]);
            setSelectedApplication(null);
        }
    }, [data.preset_industry_id]);

    // Update selected application details
    useEffect(() => {
        if (data.preset_application_id) {
            const app = applications.find(a => a.id === parseInt(data.preset_application_id));
            setSelectedApplication(app);
        } else {
            setSelectedApplication(null);
        }
    }, [data.preset_application_id, applications]);

    // Sync dropdowns: when class number changes, update purity selection
    const handleClassChange = (type, value) => {
        console.log('handleClassChange:', type, value);
        setData(type, value);
        
        // Highlight the synced purity dropdown briefly
        const fieldMap = {
            'particulate_class': 'particulate',
            'water_class': 'water',
            'oil_class': 'oil'
        };
        
        const field = fieldMap[type];
        if (field) {
            setHighlightedFields(prev => ({ ...prev, [field]: true }));
            setTimeout(() => {
                setHighlightedFields(prev => ({ ...prev, [field]: false }));
            }, 500);
        }
    };

    // Sync dropdowns: when purity changes, update class number
    const handlePurityChange = (type, purityDescription) => {
        console.log('=== handlePurityChange START ===');
        console.log('type:', type);
        console.log('purityDescription:', purityDescription);
        
        // Map field names correctly
        const fieldMap = {
            'particulate_class': 'particle',
            'water_class': 'water',
            'oil_class': 'oil'
        };
        
        const field = fieldMap[type];
        console.log('field:', field);
        console.log('purityLevels[field]:', purityLevels[field]);
        
        // Safety check
        if (!purityLevels[field] || !purityDescription) {
            console.log('Safety check failed');
            return;
        }
        
        console.log('Starting find...');
        
        // Compare against single-line version since that's what we display
        const level = purityLevels[field].find(p => {
            if (!p.description) return false;
            const singleLine = p.description.replace(/\n/g, ' ');
            const matches = singleLine === purityDescription;
            console.log('Comparing:');
            console.log('  p.level:', p.level);
            console.log('  singleLine:', singleLine);
            console.log('  matches:', matches);
            return matches;
        });
        
        console.log('Found level:', level);
        
        if (level) {
            console.log('Setting data to:', String(level.level));
            setData(type, String(level.level));
            
            // Highlight the synced class dropdown briefly
            const fieldMap = {
                'particulate_class': 'particulate',
                'water_class': 'water',
                'oil_class': 'oil'
            };
            
            const fieldName = fieldMap[type];
            if (fieldName) {
                setHighlightedFields(prev => ({ ...prev, [fieldName]: true }));
                setTimeout(() => {
                    setHighlightedFields(prev => ({ ...prev, [fieldName]: false }));
                }, 500);
            }
        }
        console.log('=== handlePurityChange END ===');
    };

    // Apply preset
    const handleApplyPreset = () => {
        if (selectedApplication) {
            setData({
                ...data,
                particulate_class: selectedApplication.particulate_class,
                water_class: selectedApplication.water_class,
                oil_class: selectedApplication.oil_class,
            });

            // Trigger highlight animation
            setHighlightedFields({
                particulate: true,
                water: true,
                oil: true
            });

            // Remove highlight after animation
            setTimeout(() => {
                setHighlightedFields({
                    particulate: false,
                    water: false,
                    oil: false
                });
            }, 1000);
        }
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/configuration/generate');
    };

    // Get current purity description for a class (single-line version)
    const getPurityDescription = (type, classValue) => {
        console.log('getPurityDescription:', type, classValue);
        if (!classValue) return '';
        
        // Map field names correctly
        const fieldMap = {
            'particulate_class': 'particle',
            'water_class': 'water',
            'oil_class': 'oil'
        };
        
        const field = fieldMap[type];
        
        // Safety check
        if (!purityLevels[field]) return '';
        
        const level = purityLevels[field].find(p => String(p.level) === String(classValue));
        console.log('Found purity level:', level);
        
        if (!level || !level.description) return '';
        
        const result = level.description.replace(/\n/g, ' ');
        console.log('Returning description:', result);
        return result;
    };

    return (
        <div className="min-h-screen bg-gray-100">
            <Head title="Configuration Tool" />
            
            <header className="bg-white shadow">
                <div className="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <h1 className="text-3xl font-bold text-gray-900">
                        Air Compressor Configuration Tool
                    </h1>
                </div>
            </header>

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <form onSubmit={handleSubmit} className="space-y-6">
                                
                                {/* Presets Accordion */}
                                <div className="border rounded-lg">
                                    <button
                                        type="button"
                                        onClick={() => setPresetOpen(!presetOpen)}
                                        className="w-full px-4 py-3 flex justify-between items-center bg-gray-50 hover:bg-gray-100 rounded-lg transition"
                                    >
                                        <span className="font-medium text-gray-900">
                                            Industry and Application Presets
                                        </span>
                                        <span className="text-gray-500">
                                            {presetOpen ? '▲' : '▼'}
                                        </span>
                                    </button>

                                    {presetOpen && (
                                        <div className="p-4 space-y-4 border-t">
                                            {/* Industry Dropdown */}
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    Industry
                                                </label>
                                                <select
                                                    value={data.preset_industry_id}
                                                    onChange={(e) => setData('preset_industry_id', e.target.value)}
                                                    className="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                >
                                                    <option value="">Select an industry...</option>
                                                    {industries.map((industry) => (
                                                        <option key={industry.id} value={industry.id}>
                                                            {industry.name}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>

                                            {/* Application Dropdown */}
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    Application
                                                </label>
                                                <select
                                                    value={data.preset_application_id}
                                                    onChange={(e) => setData('preset_application_id', e.target.value)}
                                                    className="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                    disabled={!data.preset_industry_id}
                                                >
                                                    <option value="">
                                                        {data.preset_industry_id ? 'Select an application...' : 'Select an industry first'}
                                                    </option>
                                                    {applications.map((app) => (
                                                        <option key={app.id} value={app.id}>
                                                            {app.name}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>

                                            {/* Application Description */}
                                            {selectedApplication && selectedApplication.description && (
                                                <div className="bg-blue-50 border border-blue-200 rounded-md p-3">
                                                    <p className="text-sm text-gray-700">
                                                        {selectedApplication.description}
                                                    </p>
                                                </div>
                                            )}

                                            {/* Apply Button */}
                                            <button
                                                type="button"
                                                onClick={handleApplyPreset}
                                                disabled={!selectedApplication}
                                                className="w-full bg-blue-600 text-white py-2 px-4 rounded-md hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed font-medium"
                                            >
                                                Apply Preset
                                            </button>
                                        </div>
                                    )}
                                </div>

                                {/* ISO Class Selection */}
                                <div className="space-y-6">
                                    <h3 className="text-lg font-medium text-gray-900">ISO Class Selection</h3>

                                    {/* Particulate Class */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Particulate Class
                                        </label>
                                        <div className="grid grid-cols-2 gap-3">
                                            {/* Class Number Dropdown */}
                                            <select
                                                value={data.particulate_class}
                                                onChange={(e) => handleClassChange('particulate_class', e.target.value)}
                                                className={`border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-all duration-500 ${highlightedFields.particulate ? 'bg-yellow-100' : ''}`}
                                                required
                                            >
                                                <option value="">Class...</option>
                                                {purityLevels.particle.map((item) => (
                                                    <option key={item.level} value={item.level}>
                                                        {item.level}
                                                    </option>
                                                ))}
                                            </select>

                                            {/* Purity Description Dropdown */}
                                            <select
                                                value={getPurityDescription('particulate_class', data.particulate_class)}
                                                onChange={(e) => handlePurityChange('particulate_class', e.target.value)}
                                                className={`border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm transition-all duration-500 ${highlightedFields.particulate ? 'bg-yellow-100' : ''}`}
                                                required
                                            >
                                                <option value="">Purity...</option>
                                                {purityLevels.particle.map((item) => {
                                                    const singleLine = item.description.replace(/\n/g, ' ');
                                                    return (
                                                        <option key={item.level} value={singleLine} title={item.description}>
                                                            {singleLine}
                                                        </option>
                                                    );
                                                })}
                                            </select>
                                        </div>
                                        {errors.particulate_class && (
                                            <p className="mt-1 text-sm text-red-600">{errors.particulate_class}</p>
                                        )}
                                    </div>

                                    {/* Water Class */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Water Class
                                        </label>
                                        <div className="grid grid-cols-2 gap-3">
                                            {/* Class Number Dropdown */}
                                            <select
                                                value={data.water_class}
                                                onChange={(e) => handleClassChange('water_class', e.target.value)}
                                                className={`border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-all duration-500 ${highlightedFields.water ? 'bg-yellow-100' : ''}`}
                                                required
                                            >
                                                <option value="">Class...</option>
                                                {purityLevels.water.map((item) => (
                                                    <option key={item.level} value={item.level}>
                                                        {item.level}
                                                    </option>
                                                ))}
                                            </select>

                                            {/* Purity Description Dropdown */}
                                            <select
                                                value={getPurityDescription('water_class', data.water_class)}
                                                onChange={(e) => handlePurityChange('water_class', e.target.value)}
                                                className={`border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm transition-all duration-500 ${highlightedFields.water ? 'bg-yellow-100' : ''}`}
                                                required
                                            >
                                                <option value="">Purity...</option>
                                                {purityLevels.water.map((item) => {
                                                    const singleLine = item.description.replace(/\n/g, ' ');
                                                    return (
                                                        <option key={item.level} value={singleLine} title={item.description}>
                                                            {singleLine}
                                                        </option>
                                                    );
                                                })}
                                            </select>
                                        </div>
                                        {errors.water_class && (
                                            <p className="mt-1 text-sm text-red-600">{errors.water_class}</p>
                                        )}
                                    </div>

                                    {/* Oil Class */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Total Oil Class
                                        </label>
                                        <div className="grid grid-cols-2 gap-3">
                                            {/* Class Number Dropdown */}
                                            <select
                                                value={data.oil_class}
                                                onChange={(e) => handleClassChange('oil_class', e.target.value)}
                                                className={`border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 transition-all duration-500 ${highlightedFields.oil ? 'bg-yellow-100' : ''}`}
                                                required
                                            >
                                                <option value="">Class...</option>
                                                {purityLevels.oil.map((item) => (
                                                    <option key={item.level} value={item.level}>
                                                        {item.level}
                                                    </option>
                                                ))}
                                            </select>

                                            {/* Purity Description Dropdown */}
                                            <select
                                                value={getPurityDescription('oil_class', data.oil_class)}
                                                onChange={(e) => handlePurityChange('oil_class', e.target.value)}
                                                className={`border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm transition-all duration-500 ${highlightedFields.oil ? 'bg-yellow-100' : ''}`}
                                                required
                                            >
                                                <option value="">Purity...</option>
                                                {purityLevels.oil.map((item) => {
                                                    const singleLine = item.description.replace(/\n/g, ' ');
                                                    return (
                                                        <option key={item.level} value={singleLine} title={item.description}>
                                                            {singleLine}
                                                        </option>
                                                    );
                                                })}
                                            </select>
                                        </div>
                                        {errors.oil_class && (
                                            <p className="mt-1 text-sm text-red-600">{errors.oil_class}</p>
                                        )}
                                    </div>

                                    {/* Flow Input */}
                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Flow (CFM) - Optional
                                        </label>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            value={data.flow}
                                            onChange={(e) => setData('flow', e.target.value)}
                                            placeholder="Leave empty to see all ranges"
                                            className="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        />
                                        {errors.flow && (
                                            <p className="mt-1 text-sm text-red-600">{errors.flow}</p>
                                        )}
                                    </div>
                                </div>

                                {/* Submit Button */}
                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full bg-blue-600 text-white py-3 px-4 rounded-md hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed font-medium"
                                >
                                    {processing ? 'Generating...' : 'Generate Configurations'}
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}