import React, { useState, useEffect } from 'react';
import { Head, useForm } from '@inertiajs/react';

export default function Index({ industries, purityLevels }) {
    const [mode, setMode] = useState(null);
    const [applications, setApplications] = useState([]);
    
    const { data, setData, post, processing, errors } = useForm({
        mode: null,
        industry_id: '',
        application_id: '',
        particulate_class: '',
        water_class: '',
        oil_class: '',
        flow: '',
    });

    useEffect(() => {
        if (data.industry_id) {
            fetch(`/configuration/applications/${data.industry_id}`)
                .then(res => res.json())
                .then(result => {
                    setApplications(result.applications);
                    setData('application_id', '');
                });
        } else {
            setApplications([]);
        }
    }, [data.industry_id]);

    const handleModeSelect = (selectedMode) => {
        setMode(selectedMode);
        setData('mode', selectedMode);
    };

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/configuration/generate');
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
                            {!mode && (
                                <div>
                                    <h3 className="text-lg font-medium mb-4">Select Input Method</h3>
                                    <div className="grid grid-cols-2 gap-4">
                                        <button
                                            onClick={() => handleModeSelect('industry')}
                                            className="p-6 border-2 border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition"
                                        >
                                            <div className="font-semibold text-lg mb-2">Industry & Application</div>
                                            <div className="text-sm text-gray-600">
                                                Select your industry and specific application
                                            </div>
                                        </button>

                                        <button
                                            onClick={() => handleModeSelect('iso')}
                                            className="p-6 border-2 border-gray-300 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition"
                                        >
                                            <div className="font-semibold text-lg mb-2">ISO Class Direct</div>
                                            <div className="text-sm text-gray-600">
                                                Enter ISO purity classes directly
                                            </div>
                                        </button>
                                    </div>
                                </div>
                            )}

                            {mode === 'industry' && (
                                <form onSubmit={handleSubmit} className="space-y-6">
                                    <div className="flex justify-between items-center mb-4">
                                        <h3 className="text-lg font-medium">Industry & Application Input</h3>
                                        <button
                                            type="button"
                                            onClick={() => setMode(null)}
                                            className="text-sm text-blue-600 hover:text-blue-800"
                                        >
                                            Change Method
                                        </button>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Industry
                                        </label>
                                        <select
                                            value={data.industry_id}
                                            onChange={(e) => setData('industry_id', e.target.value)}
                                            className="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            required
                                        >
                                            <option value="">Select an industry...</option>
                                            {industries.map((industry) => (
                                                <option key={industry.id} value={industry.id}>
                                                    {industry.name}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.industry_id && (
                                            <p className="mt-1 text-sm text-red-600">{errors.industry_id}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Application
                                        </label>
                                        <select
                                            value={data.application_id}
                                            onChange={(e) => setData('application_id', e.target.value)}
                                            className="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            disabled={!data.industry_id}
                                            required
                                        >
                                            <option value="">
                                                {data.industry_id ? 'Select an application...' : 'Select an industry first'}
                                            </option>
                                            {applications.map((app) => (
                                                <option key={app.id} value={app.id}>
                                                    {app.name}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.application_id && (
                                            <p className="mt-1 text-sm text-red-600">{errors.application_id}</p>
                                        )}
                                    </div>

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

                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="w-full bg-blue-600 text-white py-3 px-4 rounded-md hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed font-medium"
                                    >
                                        {processing ? 'Generating...' : 'Generate Configurations'}
                                    </button>
                                </form>
                            )}

                            {mode === 'iso' && (
                                <form onSubmit={handleSubmit} className="space-y-6">
                                    <div className="flex justify-between items-center mb-4">
                                        <h3 className="text-lg font-medium">ISO Class Input</h3>
                                        <button
                                            type="button"
                                            onClick={() => setMode(null)}
                                            className="text-sm text-blue-600 hover:text-blue-800"
                                        >
                                            Change Method
                                        </button>
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Particulate Class
                                        </label>
                                        <select
                                            value={data.particulate_class}
                                            onChange={(e) => setData('particulate_class', e.target.value)}
                                            className="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            required
                                        >
                                            <option value="">Select particulate class...</option>
                                            {purityLevels.particle.map((item) => (
                                                <option key={item.level} value={item.level}>
                                                    Class {item.level}: {item.description}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.particulate_class && (
                                            <p className="mt-1 text-sm text-red-600">{errors.particulate_class}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Water Class
                                        </label>
                                        <select
                                            value={data.water_class}
                                            onChange={(e) => setData('water_class', e.target.value)}
                                            className="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            required
                                        >
                                            <option value="">Select water class...</option>
                                            {purityLevels.water.map((item) => (
                                                <option key={item.level} value={item.level}>
                                                    Class {item.level}: {item.description}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.water_class && (
                                            <p className="mt-1 text-sm text-red-600">{errors.water_class}</p>
                                        )}
                                    </div>

                                    <div>
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Total Oil Class
                                        </label>
                                        <select
                                            value={data.oil_class}
                                            onChange={(e) => setData('oil_class', e.target.value)}
                                            className="w-full border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            required
                                        >
                                            <option value="">Select oil class...</option>
                                            {purityLevels.oil.map((item) => (
                                                <option key={item.level} value={item.level}>
                                                    Class {item.level}: {item.description}
                                                </option>
                                            ))}
                                        </select>
                                        {errors.oil_class && (
                                            <p className="mt-1 text-sm text-red-600">{errors.oil_class}</p>
                                        )}
                                    </div>

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

                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="w-full bg-blue-600 text-white py-3 px-4 rounded-md hover:bg-blue-700 disabled:bg-gray-400 disabled:cursor-not-allowed font-medium"
                                    >
                                        {processing ? 'Generating...' : 'Generate Configurations'}
                                    </button>
                                </form>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}