import React, { useState } from 'react';
import { Head, Link } from '@inertiajs/react';

export default function Results({ result }) {
    const [currentIndex, setCurrentIndex] = useState(0);
    
    const hasConfigurations = result.configurations && result.configurations.length > 0;
    const currentConfig = hasConfigurations ? result.configurations[currentIndex] : null;

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

    return (
        <div className="min-h-screen bg-gray-100">
            <Head title="Configuration Results" />

            <header className="bg-white shadow">
                <div className="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <h1 className="text-3xl font-bold text-gray-900">
                        Configuration Results
                    </h1>
                </div>
            </header>

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="mb-4">
                        <Link
                            href="/configuration"
                            className="text-blue-600 hover:text-blue-800 font-medium"
                        >
                            ← Back to Input
                        </Link>
                    </div>

                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                        <div className="p-6">
                            <h3 className="font-semibold text-lg mb-3">Your Input</h3>
                            <div className="grid grid-cols-2 gap-4 text-sm">
                                {result.input.mode === 'industry' && (
                                    <>
                                        <div>
                                            <span className="font-medium">Industry:</span> {result.input.industry}
                                        </div>
                                        <div>
                                            <span className="font-medium">Application:</span> {result.input.application}
                                        </div>
                                    </>
                                )}
                                {result.input.mode === 'iso' && (
                                    <>
                                        <div>
                                            <span className="font-medium">Particulate Class:</span> {result.input.particulate_class}
                                        </div>
                                        <div>
                                            <span className="font-medium">Water Class:</span> {result.input.water_class}
                                        </div>
                                        <div>
                                            <span className="font-medium">Oil Class:</span> {result.input.oil_class}
                                        </div>
                                    </>
                                )}
                                <div>
                                    <span className="font-medium">ISO Class:</span> {result.input.iso_class_display}
                                </div>
                                <div>
                                    <span className="font-medium">Flow:</span> {result.input.flow ? `${result.input.flow} CFM` : 'All Ranges'}
                                </div>
                            </div>
                        </div>
                    </div>

                    {!hasConfigurations ? (
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6 text-center">
                                <div className="text-yellow-600 text-lg font-medium mb-2">
                                    No Compatible Configurations Found
                                </div>
                                <p className="text-gray-600">
                                    {result.message}
                                </p>
                            </div>
                        </div>
                    ) : (
                        <div>
                            <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-4">
                                <div className="p-4 flex justify-between items-center">
                                    <div className="text-sm text-gray-600">
                                        Showing configuration {currentIndex + 1} of {result.configurations.length}
                                    </div>
                                    
                                    {result.configurations.length > 1 && (
                                        <div className="flex gap-2">
                                            <button
                                                onClick={handlePrevious}
                                                disabled={currentIndex === 0}
                                                className="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed"
                                            >
                                                ← Previous
                                            </button>
                                            <button
                                                onClick={handleNext}
                                                disabled={currentIndex === result.configurations.length - 1}
                                                className="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300 disabled:bg-gray-100 disabled:text-gray-400 disabled:cursor-not-allowed"
                                            >
                                                Next →
                                            </button>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {currentConfig && (
                                <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                                    <div className="p-6">
                                        <div className="mb-6">
                                            <h3 className="text-xl font-semibold mb-2">
                                                {currentConfig.product_range}
                                            </h3>
                                            <div className="text-sm text-gray-600 space-y-1">
                                                <div>
                                                    <span className="font-medium">Dryer Type:</span> {currentConfig.dryer_type}
                                                </div>
                                                <div>
                                                    <span className="font-medium">Flow Range:</span> {currentConfig.flow_range} CFM
                                                </div>
                                                <div>
                                                    <span className="font-medium">Compressor:</span> {currentConfig.compressor}
                                                </div>
                                            </div>
                                        </div>

                                        <div>
                                            <h4 className="font-medium text-sm text-gray-700 mb-3">Component Flow:</h4>
                                            <div className="flex items-center gap-3 overflow-x-auto pb-4">
                                                <div className="flex-shrink-0 bg-blue-100 border-2 border-blue-500 rounded-lg px-4 py-3 text-center min-w-[120px]">
                                                    <div className="font-semibold text-blue-900">
                                                        {currentConfig.compressor}
                                                    </div>
                                                </div>

                                                <div className="flex-shrink-0 text-gray-400 text-2xl">→</div>

                                                {currentConfig.components.map((component, index) => (
                                                    <React.Fragment key={index}>
                                                        <div className="flex-shrink-0 bg-gray-100 border-2 border-gray-300 rounded-lg px-4 py-3 text-center min-w-[120px]">
                                                            <div className="font-medium text-gray-900 text-sm">
                                                                {component}
                                                            </div>
                                                        </div>
                                                        {index < currentConfig.components.length - 1 && (
                                                            <div className="flex-shrink-0 text-gray-400 text-2xl">→</div>
                                                        )}
                                                    </React.Fragment>
                                                ))}
                                            </div>
                                        </div>

                                        <div className="mt-6 pt-6 border-t">
                                            <button className="w-full bg-green-600 text-white py-3 px-4 rounded-md hover:bg-green-700 font-medium">
                                                Export to PDF
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}