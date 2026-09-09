import React from 'react';
import AppLayout from '@/layouts/app-layout';
import {
    Mill,
    MillEdit,
} from '@/types';

type PrimitiveOrNested = string | number | boolean | null | undefined | any[] | Record<string, any>;

interface MillDiffProps {
    original: Record<string, PrimitiveOrNested>;
    submitted: Record<string, PrimitiveOrNested>;
    mill?: Mill;
    millEdit?: MillEdit;
}

export default function MillEditShow({ original, submitted }: MillDiffProps) {
    
    console.log('WTF: ', {original, submitted});

    // Type-safe comparison check
    const isChanged = (key: string): boolean => {
        // changing to non-strict equivalence to allow string/number equivalence
        // const helf = {
        //     oog: original[key],
        //     og: JSON.stringify(original[key]),
        //     nbcbs: JSON.stringify(submitted[key]),
        //     onbcbs: submitted[key],
        // };
        // console.log(`helf.${key}`, helf);
        // if original[key] is NOT not a number
        if (typeof original[key] === 'number' && typeof submitted[key] !== 'number') {
            console.log(`typeof original[${key}]: ${typeof original[key]} : (${original[key]})`);
            console.log(`typeof submitted[${key}]: ${typeof submitted[key]} : (${submitted[key]})`);
            console.log(`converting submitted[${key}] to number: (original) ${submitted[key]}`);

            submitted[key] = Number(submitted[key]);
        // } else {
        //     console.log(`original[${key}] is not a number: typeof ${original[key]} : ${typeof original[key]}`);
            // console.log(`original[${key}] is NaN? ${Number(original[key]) + ' vs ' + original[key]}`);
        }
        // console.log(`original[${key}] vs. submitted[${key}]: ${original[key]} ?== ${submitted[key]}`);
        // console.log(`JSON.stringify(original[${key}]) vs. JSON.stringify(submitted[${key}]): ${JSON.stringify(original[key])} ?== ${JSON.stringify(submitted[key])}`);
        // when would we actually need stringify?
        return JSON.stringify(original[key]) != JSON.stringify(submitted[key]);
    };

    // Helper to format values cleanly, rendering relationships & pivots readably
    const renderValue = (value: any): React.ReactNode => {
        if (value === null || value === undefined) {
            return <span className="text-slate-500 italic">(Not set)</span>;
        }

        // If it's an array (like a list of pivot relationship IDs)
        if (Array.isArray(value)) {
            if (value.length === 0) return <span className="text-slate-500 italic">[] (Empty Collection)</span>;
            return (
                <div className="flex flex-wrap gap-1.5">
                    {value.map((item, idx) => (
                        <span key={idx} className="inline-flex items-center rounded-md bg-slate-800 px-2 py-0.5 text-xs font-medium text-slate-300 ring-1 ring-slate-700/50">
                            {typeof item === 'object' ? JSON.stringify(item) : String(item)}
                        </span>
                    ))}
                </div>
            );
        }

        // If it's an unexpected object layout
        if (typeof value === 'object') {
            return <code className="text-xs bg-slate-950 p-1 rounded text-amber-400 block max-h-24 overflow-y-auto whitespace-pre-wrap">{JSON.stringify(value)}</code>;
        }

        // Booleans
        if (typeof value === 'boolean') {
            return value ? <span className="text-emerald-400 font-semibold">TRUE</span> : <span className="text-rose-400 font-semibold">FALSE</span>;
        }

        // Default strings & numeric values (FK integers included)
        return String(value);
    };

    return (
        <AppLayout>
            <div className="min-h-screen bg-slate-950 text-slate-100 p-6 md:p-10 font-sans selection:bg-emerald-500/30">
                {/* Header section */}
                <header className="mb-8">
                    <div className="flex items-center gap-3">
                        <span className="text-3xl">🔄</span>
                        <h1 className="text-2xl md:text-3xl font-bold tracking-tight text-white">Form Submission Diff Tool</h1>
                    </div>
                    <p className="mt-2 text-sm md:text-base text-slate-400">
                        Intercepted request pipeline. Comparing raw incoming request payloads against current database attributes.
                    </p>
                </header>

                {/* Visual Diff Table */}
                <main className="space-y-10">
                    <section>
                        <div className="overflow-x-auto rounded-xl border border-slate-800 bg-slate-900 shadow-xl">
                            <table className="w-full border-collapse text-left text-sm">
                                <thead>
                                    <tr className="border-b border-slate-800 bg-slate-900/50 text-slate-400 font-semibold">
                                        <th className="p-4 w-1/4">Field / Property Key</th>
                                        <th className="p-4 w-3/8">Original Value (DB)</th>
                                        <th className="p-4 w-3/8">Submitted Value (Form)</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-800/60">
                                    {/* we should probably loop over original so we don't get the extra stuff */}
                                    {/* {Object.entries(submitted).map(([key, value]) => { */}
                                    {Object.entries(original).map(([key, value]) => {
                                        const changed = isChanged(key);
                                        return (
                                            <tr 
                                                key={key} 
                                                className={`transition-colors duration-150 ${
                                                    changed ? 'bg-emerald-950/20 hover:bg-emerald-950/30' : 'hover:bg-slate-800/40'
                                                }`}
                                            >
                                                {/* Field Name */}
                                                <td className="p-4 font-semibold text-slate-300 antialiased flex items-center gap-2">
                                                    <span className="font-mono text-slate-400">{key}</span>
                                                    {changed && (
                                                        <span className="inline-flex items-center rounded-full bg-emerald-400/10 px-1.5 py-0.5 text-xs font-medium text-emerald-400 ring-1 ring-emerald-400/20" title="Data Changed">
                                                            Modified
                                                        </span>
                                                    )}
                                                </td>

                                                {/* Original DB State */}
                                                <td className={`p-4 font-mono text-slate-300 ${changed ? 'opacity-60' : ''}`}>
                                                    {renderValue(value)}
                                                    {/* {renderValue(original[key])} */}
                                                </td>

                                                {/* Incoming Form Submission */}
                                                <td className={`p-4 font-mono ${changed ? 'text-emerald-400 font-bold' : 'text-slate-300'}`}>
                                                    {/* I see, it uses 'value', which comes from whichever version we loop over. */}
                                                    {renderValue(submitted[key])}
                                                    {/* {renderValue(value)} */}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    </section>

                    {/* Optional etAl Debug Properties Block */}
                    {/* {etAl !== undefined && (
                        <section className="rounded-xl border border-slate-800 bg-slate-900 p-5 shadow-lg">
                            <h2 className="text-lg font-bold text-white mb-2 flex items-center gap-2">
                                <span>📎</span> Supplemental Information <span className="text-xs font-mono font-normal text-slate-500">(etAl Parameter)</span>
                            </h2>
                            <pre className="text-xs font-mono bg-slate-950 p-4 rounded-lg border border-slate-800/80 text-blue-300 overflow-x-auto max-h-96">
                                {JSON.stringify(etAl, null, 2)}
                            </pre>
                        </section>
                    )} */}

                    {/* Split Deep Diagnostics Codeblocks */}
                    <section>
                        <h2 className="text-lg font-semibold text-slate-400 mb-4">Raw Structural JSON Payloads</h2>
                        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div>
                                <span className="block text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">Full Original Schema</span>
                                <pre className="text-xs font-mono bg-slate-950 p-4 rounded-xl border border-slate-800 text-slate-400 overflow-x-auto max-h-72">
                                    {JSON.stringify(original, null, 2)}
                                </pre>
                            </div>
                            <div>
                                <span className="block text-xs font-medium text-slate-500 uppercase tracking-wider mb-2">Full Submitted Schema</span>
                                <pre className="text-xs font-mono bg-slate-950 p-4 rounded-xl border border-slate-800 text-slate-400 overflow-x-auto max-h-72">
                                    {JSON.stringify(submitted, null, 2)}
                                </pre>
                            </div>
                        </div>
                    </section>
                </main>
            </div>
        </AppLayout>
    );
}
