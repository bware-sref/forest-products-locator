// import React from 'react';

// Define strict types for the component props
interface DebugDumpProps {
    original: Record<string, any>;
    submitted: Record<string, any>;
}

export default function DebugDump({ original, submitted }: DebugDumpProps) {
    
    // Type-safe helper to check if a specific key changed
    const isChanged = (key: string): boolean => {
        return JSON.stringify(original[key]) !== JSON.stringify(submitted[key]);
    };

    // Helper to safely display nested structures or primitives as strings
    const renderValue = (value: any): string => {
        if (value === null || value === undefined) return '(Not set)';
        if (typeof value === 'object') return JSON.stringify(value);
        return String(value);
    };

    return (
        <div style={{ padding: '30px', fontFamily: 'sans-serif', background: '#0f172a', color: '#f8fafc', minHeight: '100vh' }}>
            <h1 style={{ marginBottom: '8px' }}>🔄 Form Submission Diff Tool</h1>
            <p style={{ color: '#94a3b8', marginBottom: '24px' }}>Intercepted and comparing form data against existing database state.</p>
            
            <table style={{ width: '100%', borderCollapse: 'collapse', background: '#1e293b', borderRadius: '8px', overflow: 'hidden' }}>
                <thead>
                    <tr style={{ background: '#334155', textAlign: 'left' }}>
                        <th style={{ padding: '12px' }}>Field Name</th>
                        <th style={{ padding: '12px' }}>Original Database Value</th>
                        <th style={{ padding: '12px' }}>Newly Submitted Value</th>
                    </tr>
                </thead>
                <tbody>
                    {Object.entries(submitted).map(([key, value]) => {
                        const changed = isChanged(key);
                        return (
                            <tr 
                                key={key} 
                                style={{ 
                                    background: changed ? '#14532d' : 'transparent', 
                                    borderBottom: '1px solid #334155',
                                    transition: 'background 0.2s ease'
                                }}
                            >
                                <td style={{ padding: '12px', fontWeight: 'bold', color: '#94a3b8' }}>
                                    {key} {changed && <span title="Value modified">📝</span>}
                                </td>
                                <td style={{ padding: '12px', fontFamily: 'monospace', color: '#cbd5e1', opacity: changed ? 0.7 : 1 }}>
                                    {renderValue(original[key])}
                                </td>
                                <td style={{ padding: '12px', fontFamily: 'monospace', color: changed ? '#4ade80' : '#cbd5e1', fontWeight: changed ? 'bold' : 'normal' }}>
                                    {renderValue(value)}
                                </td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>

            {/* Deep payload inspection backup */}
            <h2 style={{ marginTop: '40px', color: '#94a3b8', fontSize: '1.25rem' }}>Raw Payload Codeblocks</h2>
            <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '20px', marginTop: '12px' }}>
                <div>
                    <h3 style={{ fontSize: '0.875rem', color: '#64748b', marginBottom: '4px' }}>Original</h3>
                    <pre style={{ background: '#020617', padding: '15px', borderRadius: '6px', overflowX: 'auto', fontSize: '0.85rem' }}>
                        {JSON.stringify(original, null, 2)}
                    </pre>
                </div>
                <div>
                    <h3 style={{ fontSize: '0.875rem', color: '#64748b', marginBottom: '4px' }}>Submitted</h3>
                    <pre style={{ background: '#020617', padding: '15px', borderRadius: '6px', overflowX: 'auto', fontSize: '0.85rem' }}>
                        {JSON.stringify(submitted, null, 2)}
                    </pre>
                </div>
            </div>
        </div>
    );
}
