import React, { useState, useEffect, useRef } from 'react';
import { Head, useForm } from '@inertiajs/react';

// Results View Component
function ResultsView({ result, onBack, appliedPreset, presetModified, purityLevels, productDescriptions }) {
    const [currentIndex, setCurrentIndex] = useState(0);
    const [selectedFlowIndexes, setSelectedFlowIndexes] = useState({});
    const [modalProduct, setModalProduct] = useState(null);
    const [reviewMode, setReviewMode] = useState(false); // Track if in review mode
    const flowContainerRef = useRef(null);
    const [lineWidth, setLineWidth] = useState(0);
    
    const hasConfigurations = result.configurations && result.configurations.length > 0;
    const currentConfig = hasConfigurations ? result.configurations[currentIndex] : null;

    // Initialize selected flow indexes (default to 0 for each config)
    useEffect(() => {
        if (hasConfigurations) {
            const initialIndexes = {};
            result.configurations.forEach((config, idx) => {
                initialIndexes[idx] = 0;
            });
            setSelectedFlowIndexes(initialIndexes);
        }
    }, [result]);

    // Calculate line width from first to last component
    useEffect(() => {
        if (!flowContainerRef.current || !currentConfig) return;
        
        const updateLineWidth = () => {
            const container = flowContainerRef.current;
            if (!container) return;
            
            const items = container.querySelectorAll('.component-item');
            
            if (items.length < 2) {
                setLineWidth(0);
                return;
            }
            
            const first = items[0].getBoundingClientRect();
            const last = items[items.length - 1].getBoundingClientRect();
            
            // Width from center of first to center of last
            setLineWidth(last.left - first.left);
        };
        
        // Delay to ensure images have loaded and sized
        const timeoutId = setTimeout(updateLineWidth, 150);
        
        // Also update on image load
        const images = flowContainerRef.current?.querySelectorAll('img');
        images?.forEach(img => {
            if (img.complete) {
                updateLineWidth();
            } else {
                img.addEventListener('load', updateLineWidth);
            }
        });
        
        window.addEventListener('resize', updateLineWidth);
        
        return () => {
            clearTimeout(timeoutId);
            window.removeEventListener('resize', updateLineWidth);
            images?.forEach(img => {
                img.removeEventListener('load', updateLineWidth);
            });
        };
    }, [currentConfig, selectedFlowIndexes, currentIndex, reviewMode]);

    /**
     * Get product info with smart matching
     * Tries exact match first (e.g., "QCMD 12-64"), then falls back to base code (e.g., "QCMD")
     */
    const getProductDescription = (componentName) => {
        // Try exact match first
        if (productDescriptions[componentName]) {
            return productDescriptions[componentName];
        }
        
        // Fall back to base code (remove flow range suffix)
        // "QCMD 12-64" -> "QCMD"
        // "QHD 230-635" -> "QHD"
        const baseCode = componentName.split(' ')[0];
        if (productDescriptions[baseCode]) {
            return productDescriptions[baseCode];
        }
        
        return null;
    };

    const handlePrevious = () => {
        if (currentIndex > 0) {
            setCurrentIndex(currentIndex - 1);
        }
    };

    const handleNext = () => {
        if (currentIndex < result.configurations.length - 1) {
            setCurrentIndex(currentIndex + 1);
        }
    };

    const handleFlowOptionChange = (e) => {
        setSelectedFlowIndexes({
            ...selectedFlowIndexes,
            [currentIndex]: parseInt(e.target.value)
        });
    };

    const selectedFlowOption = currentConfig && currentConfig.flow_options 
        ? currentConfig.flow_options[selectedFlowIndexes[currentIndex] || 0]
        : null;

    const selectedComponentConfig = currentConfig && currentConfig.component_configurations
        ? currentConfig.component_configurations[selectedFlowIndexes[currentIndex] || 0]
        : null;

    // Get purity description for each class
    const getPurityDescription = (type, classValue) => {
        if (!classValue) return '';
        const level = purityLevels[type]?.find(p => String(p.level) === String(classValue));
        return level ? level.description : '';
    };

    const handleExportPdf = () => {
        // Prepare data for PDF export
        const exportData = {
            particulate_class: result.input.particulate_class,
            water_class: result.input.water_class,
            oil_class: result.input.oil_class,
            flow: result.input.flow,
            config_index: currentIndex,
            flow_index: selectedFlowIndexes[currentIndex] || 0,
            preset: appliedPreset ? `${appliedPreset.industry}: ${appliedPreset.application}${presetModified ? ' (modified)' : ''}` : null,
        };

        // Create form and submit to trigger download
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '/configuration/export-pdf';
        form.target = '_blank';

        // Add CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        form.appendChild(csrfInput);

        // Add all data fields
        Object.keys(exportData).forEach(key => {
            if (exportData[key] !== null && exportData[key] !== undefined) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = key;
                input.value = exportData[key];
                form.appendChild(input);
            }
        });

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    };

    return (
        <div className="space-y-6">
            {/* Input Summary */}
            <div className="bg-white overflow-hidden shadow-sm sm:rounded-2xl">
                <div className="p-6">
                    {/* ISO Configuration as Table */}
                    <div className="mb-4">
                        <table className="w-full border-collapse overflow-hidden rounded-lg">
                            <thead>
                                <tr className="bg-[#00387B]">
                                    <th className="text-center py-2 px-3 font-semibold text-sm border-r border-white text-white rounded-tl-lg">Particulate</th>
                                    <th className="text-center py-2 px-3 font-semibold text-sm border-r border-white text-white">Water</th>
                                    <th className="text-center py-2 px-3 font-semibold text-sm text-white rounded-tr-lg">Oil</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr className="border-b border-gray-200">
                                    <td className="py-2 px-3 font-medium text-center border-r border-gray-300">{result.input.particulate_class}</td>
                                    <td className="py-2 px-3 font-medium text-center border-r border-gray-300">{result.input.water_class}</td>
                                    <td className="py-2 px-3 font-medium text-center">{result.input.oil_class}</td>
                                </tr>
                                <tr>
                                    <td className="py-2 px-3 text-sm text-gray-600 text-center border-r border-gray-300 whitespace-pre-line">
                                        {getPurityDescription('particle', result.input.particulate_class)}
                                    </td>
                                    <td className="py-2 px-3 text-sm text-gray-600 text-center border-r border-gray-300">
                                        {getPurityDescription('water', result.input.water_class)}
                                    </td>
                                    <td className="py-2 px-3 text-sm text-gray-600 text-center">
                                        {getPurityDescription('oil', result.input.oil_class)}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    {/* Preset and Flow */}
                    <div className="text-sm space-y-1">
                        {appliedPreset && (
                            <div>
                                <span className="font-medium">Industry/Application:</span> {appliedPreset.industry}: {appliedPreset.application}
                                {presetModified && <span className="text-gray-500 italic ml-2">(modified)</span>}
                            </div>
                        )}
                        <div>
                            <span className="font-medium">Flow:</span> {result.input.flow ? `${result.input.flow} CFM` : 'All Ranges'}
                        </div>
                    </div>
                </div>
            </div>

            {/* Results */}
            {!hasConfigurations ? (
                <div className="bg-white overflow-hidden shadow-sm sm:rounded-2xl">
                    <div className="p-6 text-center">
                        <div className="text-yellow-600 text-lg font-medium mb-2">
                            No Compatible Configurations Found
                        </div>
                        <p className="text-gray-600 mb-6">
                            {result.message}
                        </p>
                        <button
                            onClick={onBack}
                            className="bg-gray-200 text-gray-700 py-3 px-6 rounded-md hover:bg-gray-300 font-medium"
                        >
                            ← Back to Input
                        </button>
                    </div>
                </div>
            ) : (
                <div className="bg-white overflow-hidden shadow-sm sm:rounded-2xl">
                    {currentConfig && selectedFlowOption && selectedComponentConfig && (
                        <div className="p-6">
                            {/* Flow Range Info */}
                            <div className="mb-6">
                                <div className="text-sm text-gray-600">
                                    <span className="font-medium">Flow Range:</span>{' '}
                                    {!reviewMode && currentConfig.flow_options.length > 1 ? (
                                        <select
                                            value={selectedFlowIndexes[currentIndex] || 0}
                                            onChange={handleFlowOptionChange}
                                            className="border-gray-300 rounded-md shadow-sm focus:border-[#00387B] focus:ring-[#00387B] text-sm"
                                        >
                                            {currentConfig.flow_options.map((option, idx) => (
                                                <option key={idx} value={idx}>
                                                    {option.flow_range} CFM
                                                </option>
                                            ))}
                                        </select>
                                    ) : (
                                        <span className="inline-block" style={{ paddingTop: '9px', paddingBottom: '9px' }}>{selectedFlowOption.flow_range} CFM</span>
                                    )}
                                </div>
                            </div>

                            {/* Component Flow */}
                            <div>
                                <div className="relative">
                                    <div ref={flowContainerRef} className="flex items-start gap-3 overflow-x-auto pb-4 relative">
                                        {/* Connecting line - dynamically sized from first to last component */}
                                        {lineWidth > 0 && (
                                            <div className="absolute top-[60px] left-[60px] h-0.5 bg-gray-400 z-0" style={{
                                                width: `${lineWidth}px`
                                            }}></div>
                                        )}
                                        {/* Compressor */}
                                        <div className="flex flex-col items-center gap-2 component-item relative z-10">
                                            <div 
                                                onClick={() => {
                                                    const productInfo = getProductDescription(currentConfig.compressor);
                                                    if (productInfo) setModalProduct({ 
                                                        name: currentConfig.compressor, 
                                                        ...productInfo 
                                                    });
                                                }}
                                                className={`flex-shrink-0 flex flex-col items-center gap-2 ${getProductDescription(currentConfig.compressor) ? 'cursor-pointer' : ''}`}
                                            >
                                                {/* Image */}
                                                <div className="h-[120px] flex items-center justify-center overflow-hidden p-2">
                                                    {getProductDescription(currentConfig.compressor)?.image_path ? (
                                                        <img 
                                                            src={getProductDescription(currentConfig.compressor).image_path} 
                                                            alt={currentConfig.compressor}
                                                            className="h-full w-auto object-contain transition-transform hover:scale-110"
                                                            style={{ maxWidth: 'none' }}
                                                            loading="eager"
                                                        />
                                                    ) : (
                                                        <div className="text-gray-400 text-xs text-center px-2 bg-white">No Image</div>
                                                    )}
                                                </div>
                                                {/* Name */}
                                                <div className="font-medium text-gray-900 text-sm text-center">
                                                    {currentConfig.compressor}
                                                </div>
                                            </div>
                                        </div>

                                        {selectedComponentConfig.components.map((component, index) => {
                                            // Determine if this is the dryer component by checking if it matches the product_range
                                            const isDryer = selectedFlowOption && component.includes(selectedFlowOption.product_range.split(' ')[0]);
                                            
                                            return (
                                                <div key={index} className="flex flex-col items-center gap-2 component-item relative z-10">
                                                    <div 
                                                        onClick={() => {
                                                            const productInfo = getProductDescription(component);
                                                            if (productInfo) setModalProduct({ 
                                                                name: component, 
                                                                ...productInfo 
                                                            });
                                                        }}
                                                        className={`flex-shrink-0 flex flex-col items-center gap-2 ${getProductDescription(component) ? 'cursor-pointer' : ''}`}
                                                    >
                                                        {/* Image */}
                                                        <div className="h-[120px] flex items-center justify-center overflow-hidden p-2">
                                                            {getProductDescription(component)?.image_path ? (
                                                                <img 
                                                                    src={getProductDescription(component).image_path} 
                                                                    alt={component}
                                                                    className="h-full w-auto object-contain transition-transform hover:scale-110"
                                                                    style={{ maxWidth: 'none' }}
                                                                    loading="eager"
                                                                />
                                                            ) : (
                                                                <div className="text-gray-400 text-xs text-center px-2 bg-white">No Image</div>
                                                            )}
                                                        </div>
                                                        {/* Name */}
                                                        <div className="font-medium text-gray-900 text-sm text-center">
                                                            {component}
                                                        </div>
                                                    </div>
                                                    
                                                    {/* Navigation arrows under dryer component */}
                                                    {!reviewMode && isDryer && result.configurations.length > 1 && (
                                                        <div className="flex flex-col items-center gap-1 mt-2">
                                                            <div className="flex gap-2">
                                                                <button
                                                                    onClick={handlePrevious}
                                                                    disabled={currentIndex === 0}
                                                                    className="px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed text-sm"
                                                                >
                                                                    ←
                                                                </button>
                                                                <button
                                                                    onClick={handleNext}
                                                                    disabled={currentIndex === result.configurations.length - 1}
                                                                    className="px-3 py-1 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed text-sm"
                                                                >
                                                                    →
                                                                </button>
                                                            </div>
                                                            <div className="text-xs text-gray-500">
                                                                {currentIndex + 1} of {result.configurations.length}
                                                            </div>
                                                        </div>
                                                    )}
                                                </div>
                                            );
                                        })}
                                    </div>
                                </div>
                            </div>

                            <div className="mt-6 pt-6 border-t">
                                <div className="flex gap-3">
                                    {/* Back Button */}
                                    <button
                                        onClick={() => {
                                            if (reviewMode) {
                                                setReviewMode(false);
                                            } else {
                                                onBack();
                                            }
                                        }}
                                        className="flex-1 bg-gray-200 text-gray-700 py-3 px-4 rounded-md hover:bg-gray-300 font-medium"
                                    >
                                        ← {reviewMode ? 'Back to Selection' : 'Back to Input'}
                                    </button>
                                    
                                    {/* Action Button */}
                                {reviewMode ? (
                                    <button 
                                        onClick={handleExportPdf}
                                        className="flex-1 bg-[#00387B] text-white py-3 px-4 rounded-md hover:bg-[#00468F] font-medium"
                                    >
                                        Export to PDF
                                    </button>
                                ) : (
                                    <button 
                                        onClick={() => setReviewMode(true)}
                                        className="flex-1 bg-[#00387B] text-white py-3 px-4 rounded-md hover:bg-[#00468F] font-medium"
                                    >
                                        Select and Review
                                    </button>
                                )}
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            )}

            {/* Product Description Modal */}
            {modalProduct && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4" onClick={() => setModalProduct(null)}>
                    <div className="bg-white rounded-3xl shadow-xl max-w-2xl w-full overflow-hidden" onClick={(e) => e.stopPropagation()}>
                        {/* Blue Header Bar */}
                        <div className="bg-[#00387B] text-white px-6 py-4 flex items-center justify-between">
                            <h3 className="text-xl font-bold">{modalProduct.name}</h3>
                            <button
                                onClick={() => setModalProduct(null)}
                                className="text-white hover:text-gray-200 text-3xl font-bold leading-none"
                            >
                                ×
                            </button>
                        </div>
                        
                        {/* Modal Content */}
                        <div className="p-6">
                            {/* Product Image and Description - Side by Side */}
                            <div className="flex flex-col md:flex-row gap-4 mb-4">
                                {/* Product Image */}
                                {modalProduct.image_path && (
                                    <div className="flex-shrink-0 md:w-64">
                                        <div className="flex justify-center bg-gray-50 rounded-lg p-4">
                                            <img 
                                                src={modalProduct.image_path} 
                                                alt={modalProduct.name}
                                                className="max-w-full max-h-64 object-contain"
                                                loading="eager"
                                            />
                                        </div>
                                    </div>
                                )}
                                
                                {/* Main Description */}
                                {modalProduct.description && (
                                    <div className="flex-1 text-gray-700 whitespace-pre-line">
                                        {modalProduct.description}
                                    </div>
                                )}
                            </div>

                        {/* Notes Section */}
                        <div className="space-y-3">
                            {modalProduct.refrigerant_dryer_note && (
                                <div className="bg-[#E6EEF7] border border-[#00387B] rounded-md p-3">
                                    <p className="text-sm font-medium text-[#00387B] mb-1">Refrigerant Dryer Note:</p>
                                    <p className="text-sm text-gray-700 whitespace-pre-line">{modalProduct.refrigerant_dryer_note}</p>
                                </div>
                            )}

                            {modalProduct.desiccant_dryer_note && (
                                <div className="bg-[#E6EEF7] border border-[#00387B] rounded-md p-3">
                                    <p className="text-sm font-medium text-[#00387B] mb-1">Desiccant Dryer Note:</p>
                                    <p className="text-sm text-gray-700 whitespace-pre-line">{modalProduct.desiccant_dryer_note}</p>
                                </div>
                            )}

                            {modalProduct.qaf_note && (
                                <div className="bg-[#E6EEF7] border border-[#00387B] rounded-md p-3">
                                    <p className="text-sm font-medium text-[#00387B] mb-1">QAF Note:</p>
                                    <p className="text-sm text-gray-700 whitespace-pre-line">{modalProduct.qaf_note}</p>
                                </div>
                            )}
                        </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}

export default function Index({ industries, purityLevels, productDescriptions }) {
    const [applications, setApplications] = useState([]);
    const [selectedApplication, setSelectedApplication] = useState(null);
    const [presetOpen, setPresetOpen] = useState(false);
    const [highlightedFields, setHighlightedFields] = useState({
        particulate: false,
        water: false,
        oil: false
    });
    const [showResults, setShowResults] = useState(false);
    const [resultData, setResultData] = useState(null);
    const [appliedPreset, setAppliedPreset] = useState(null);
    const [presetModified, setPresetModified] = useState(false);
    
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
        setData(type, value);
        
        if (appliedPreset) {
            setPresetModified(true);
        }
        
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
        const fieldMap = {
            'particulate_class': 'particle',
            'water_class': 'water',
            'oil_class': 'oil'
        };
        
        const field = fieldMap[type];
        
        if (!purityLevels[field] || !purityDescription) {
            return;
        }
        
        const level = purityLevels[field].find(p => {
            if (!p.description) return false;
            const singleLine = p.description.replace(/\n/g, ' ');
            return singleLine === purityDescription;
        });
        
        if (level) {
            setData(type, String(level.level));
            
            if (appliedPreset) {
                setPresetModified(true);
            }
            
            const fieldMap2 = {
                'particulate_class': 'particulate',
                'water_class': 'water',
                'oil_class': 'oil'
            };
            
            const fieldName = fieldMap2[type];
            if (fieldName) {
                setHighlightedFields(prev => ({ ...prev, [fieldName]: true }));
                setTimeout(() => {
                    setHighlightedFields(prev => ({ ...prev, [fieldName]: false }));
                }, 500);
            }
        }
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

            const industry = industries.find(i => i.id === parseInt(data.preset_industry_id));
            setAppliedPreset({
                industry: industry?.name,
                application: selectedApplication.name
            });
            setPresetModified(false);

            setHighlightedFields({
                particulate: true,
                water: true,
                oil: true
            });

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
        
        fetch('/configuration/generate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({
                particulate_class: data.particulate_class,
                water_class: data.water_class,
                oil_class: data.oil_class,
                flow: data.flow,
            }),
        })
        .then(async res => {
            const text = await res.text();
            
            if (!res.ok) {
                throw new Error(`HTTP error! status: ${res.status}`);
            }
            
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Failed to parse JSON:', e);
                throw new Error('Server returned invalid JSON');
            }
        })
        .then(response => {
            setResultData(response.props.result);
            setShowResults(true);
        })
        .catch(error => {
            console.error('Error generating configurations:', error);
            alert('Error generating configurations. Check console for details.');
        });
    };
    
    const handleBackToInput = () => {
        setShowResults(false);
    };

    const getPurityDescription = (type, classValue) => {
        if (!classValue) return '';
        
        const fieldMap = {
            'particulate_class': 'particle',
            'water_class': 'water',
            'oil_class': 'oil'
        };
        
        const field = fieldMap[type];
        
        if (!purityLevels[field]) return '';
        
        const level = purityLevels[field].find(p => String(p.level) === String(classValue));
        
        if (!level || !level.description) return '';
        
        return level.description.replace(/\n/g, ' ');
    };

    return (
        <div className="min-h-screen bg-[#E8EAED]">
            <Head title="Configuration Tool" />
            
            <header className="bg-white shadow">
                <div className="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <div className="flex items-center justify-between">
                        <img 
                            src="/images/quincy-logo.png" 
                            alt="Quincy Compressor" 
                            className="h-12"
                        />
                        <h1 className="text-2xl font-bold text-gray-900">
                            ISO 8573-1-2010 Air Purity Class Selection Tool
                        </h1>
                    </div>
                </div>
            </header>

            <div className="py-12">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    {!showResults ? (
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-2xl">
                            {/* Blue header bar */}
                            <div className="bg-[#00387B] py-4"></div>
                            
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
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    Industry
                                                </label>
                                                <select
                                                    value={data.preset_industry_id}
                                                    onChange={(e) => setData('preset_industry_id', e.target.value)}
                                                    className="w-full border-gray-300 rounded-md shadow-sm focus:border-[#00387B] focus:ring-[#00387B]"
                                                >
                                                    <option value="">Select an industry...</option>
                                                    {industries.map((industry) => (
                                                        <option key={industry.id} value={industry.id}>
                                                            {industry.name}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    Application
                                                </label>
                                                <select
                                                    value={data.preset_application_id}
                                                    onChange={(e) => setData('preset_application_id', e.target.value)}
                                                    className="w-full border-gray-300 rounded-md shadow-sm focus:border-[#00387B] focus:ring-[#00387B]"
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

                                            {selectedApplication && selectedApplication.description && (
                                                <div className="bg-blue-50 border border-blue-200 rounded-md p-3">
                                                    <p className="text-sm text-gray-700">
                                                        {selectedApplication.description}
                                                    </p>
                                                </div>
                                            )}

                                            <button
                                                type="button"
                                                onClick={handleApplyPreset}
                                                disabled={!selectedApplication}
                                                className="w-full bg-[#00387B] text-white py-2 px-4 rounded-md hover:bg-[#00468F] disabled:bg-gray-300 disabled:cursor-not-allowed font-medium"
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
                                            <select
                                                value={data.particulate_class}
                                                onChange={(e) => handleClassChange('particulate_class', e.target.value)}
                                                className={`border-gray-300 rounded-md shadow-sm focus:border-[#00387B] focus:ring-[#00387B] transition-all duration-500 ${highlightedFields.particulate ? 'bg-blue-100' : ''}`}
                                                required
                                            >
                                                <option value="">Class...</option>
                                                {purityLevels.particle.map((item) => (
                                                    <option key={item.level} value={item.level}>
                                                        {item.level}
                                                    </option>
                                                ))}
                                            </select>

                                            <select
                                                value={getPurityDescription('particulate_class', data.particulate_class)}
                                                onChange={(e) => handlePurityChange('particulate_class', e.target.value)}
                                                className={`border-gray-300 rounded-md shadow-sm focus:border-[#00387B] focus:ring-[#00387B] text-sm transition-all duration-500 ${highlightedFields.particulate ? 'bg-blue-100' : ''}`}
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
                                            <select
                                                value={data.water_class}
                                                onChange={(e) => handleClassChange('water_class', e.target.value)}
                                                className={`border-gray-300 rounded-md shadow-sm focus:border-[#00387B] focus:ring-[#00387B] transition-all duration-500 ${highlightedFields.water ? 'bg-blue-100' : ''}`}
                                                required
                                            >
                                                <option value="">Class...</option>
                                                {purityLevels.water.map((item) => (
                                                    <option key={item.level} value={item.level}>
                                                        {item.level}
                                                    </option>
                                                ))}
                                            </select>

                                            <select
                                                value={getPurityDescription('water_class', data.water_class)}
                                                onChange={(e) => handlePurityChange('water_class', e.target.value)}
                                                className={`border-gray-300 rounded-md shadow-sm focus:border-[#00387B] focus:ring-[#00387B] text-sm transition-all duration-500 ${highlightedFields.water ? 'bg-blue-100' : ''}`}
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
                                            <select
                                                value={data.oil_class}
                                                onChange={(e) => handleClassChange('oil_class', e.target.value)}
                                                className={`border-gray-300 rounded-md shadow-sm focus:border-[#00387B] focus:ring-[#00387B] transition-all duration-500 ${highlightedFields.oil ? 'bg-blue-100' : ''}`}
                                                required
                                            >
                                                <option value="">Class...</option>
                                                {purityLevels.oil.map((item) => (
                                                    <option key={item.level} value={item.level}>
                                                        {item.level}
                                                    </option>
                                                ))}
                                            </select>

                                            <select
                                                value={getPurityDescription('oil_class', data.oil_class)}
                                                onChange={(e) => handlePurityChange('oil_class', e.target.value)}
                                                className={`border-gray-300 rounded-md shadow-sm focus:border-[#00387B] focus:ring-[#00387B] text-sm transition-all duration-500 ${highlightedFields.oil ? 'bg-blue-100' : ''}`}
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
                                            className="w-full border-gray-300 rounded-md shadow-sm focus:border-[#00387B] focus:ring-[#00387B]"
                                        />
                                        {errors.flow && (
                                            <p className="mt-1 text-sm text-red-600">{errors.flow}</p>
                                        )}
                                    </div>
                                </div>

                                <button
                                    type="submit"
                                    disabled={processing}
                                    className="w-full bg-[#00387B] text-white py-3 px-4 rounded-md hover:bg-[#00468F] disabled:bg-gray-400 disabled:cursor-not-allowed font-medium"
                                >
                                    {processing ? 'Generating...' : 'Generate Configurations'}
                                </button>
                            </form>
                        </div>
                    </div>
                    ) : (
                        <ResultsView 
                            result={resultData} 
                            onBack={handleBackToInput}
                            appliedPreset={appliedPreset}
                            presetModified={presetModified}
                            purityLevels={purityLevels}
                            productDescriptions={productDescriptions}
                        />
                    )}
                </div>
            </div>
        </div>
    );
}