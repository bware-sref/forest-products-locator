import React from 'react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import {
    Link,
    router,
} from "@inertiajs/react";
import {
    toast,
    type ExternalToast
} from "sonner";
import {
    ThumbsDown,
    ThumbsUp,
} from "lucide-react";
import {
    Mill,
    MillEdit,
} from '@/types';
import {
    approve,
    reject,
} from "@/routes/mill-edits";

type PrimitiveOrNested = string | number | boolean | null | undefined | unknown[] | Record<string, unknown>;

interface MillDiffProps {
    original: Record<string, PrimitiveOrNested>;
    submitted: Record<string, PrimitiveOrNested>;
    mill?: Mill;
    millEdit?: MillEdit;
    [key: string]: unknown;
}

export default function MillEditShow({ original, submitted, ...props }: MillDiffProps) {
    
    // console.log('WTF: ', {original, submitted, props});

    const original2 = structuredClone(original);
    const submitted2 = structuredClone(submitted);

    const rejectHash = props.millEdit?.reject_hash ?? '';
    const approveHash = props.millEdit?.approve_hash ?? '';

    const showRaw = false;

    // IMPORTANT: 
    // InertiaJS docs recommend attaching event handlers within a useEffect() method so that the event handlers will 
    // get cleaned up when the component to which they're attached is unmounted instead of accumulating in memory.
    React.useEffect(() => {
        return router.on("flash", (event) => {
            // console.log('flash event: ', event);
            if (event.detail.flash) {
                const flash = event.detail.flash;
                const message = flash.message ?? '';
                const options : ExternalToast = {
                  closeButton: true,
                  position: "top-center",
                  description: (
                    <p className="mt-2 w-[320px] overflow-x-auto rounded-md bg-code p-4 text-code-foreground">
                      {String(message)}
                    </p>
                  ),
                };
                
                if ('success' === flash.type) {
                    toast.success('Success!', options);
                } else if ('error' === flash.type) {
                    toast.error('An error occurred...', options);
                } else {
                    toast.info("A thing happened...", options);
                }
            }
        })
    })

    // Type-safe comparison check
    const isChanged = (key: string): boolean => {
        // changing to non-strict equivalence to allow string/number equivalence
        // const helf = {
        //     oog: original2[key],
        //     og: JSON.stringify(original2[key]),
        //     nbcbs: JSON.stringify(submitted2[key]),
        //     onbcbs: submitted2[key],
        // };
        // console.log(`helf.${key}`, helf);
        // if original2[key] is NOT not a number
        if (typeof original2[key] === 'number' && typeof submitted2[key] !== 'number') {
            console.log(`typeof original2[${key}]: ${typeof original2[key]} : (${original2[key]})`);
            console.log(`typeof submitted2[${key}]: ${typeof submitted2[key]} : (${submitted2[key]})`);
            console.log(`converting submitted2[${key}] to number: (original2) ${submitted2[key]}`);
            // modifying component props or hook arguments is a TypeScript no-no.
            // the rec is to use a local variable instead...
            submitted2[key] = Number(submitted2[key]);
        // } else {
        //     console.log(`original2[${key}] is not a number: typeof ${original2[key]} : ${typeof original2[key]}`);
            // console.log(`original2[${key}] is NaN? ${Number(original2[key]) + ' vs ' + original2[key]}`);
        }
        // console.log(`original2[${key}] vs. submitted2[${key}]: ${original2[key]} ?== ${submitted2[key]}`);
        // console.log(`JSON.stringify(original2[${key}]) vs. JSON.stringify(submitted2[${key}]): ${JSON.stringify(original2[key])} ?== ${JSON.stringify(submitted2[key])}`);
        // when would we actually need stringify?
        return JSON.stringify(original2[key]) != JSON.stringify(submitted2[key]);
    };

    // Helper to format values cleanly, rendering relationships & pivots readably
    // renderValue seems like it should be privy to the value of change for the value in question
    const renderValue = (value: unknown, changed : boolean = false): React.ReactNode => {
        if (value === null || value === undefined) {
            return <span className="Xtext-slate-500 text-beluga/60 italic">(Not set)</span>;
        }

        // If it's an array (like a list of pivot relationship IDs)
        if (Array.isArray(value)) {
            if (value.length === 0) return <span className="text-slate-500 italic">[] (Empty Collection)</span>;
            return (
                <div className="flex flex-wrap gap-1.5">
                    {value.map((item, idx) => (
                        <span key={idx} className={`inline-flex items-center rounded-md px-2 py-0.5 text-xs ring-1 ${changed ? 'font-bold text-emerald-400 ring-1 ring-emerald-400/20' : 'Xbg-slate-800 bg-velvet font-medium Xtext-slate-300 text-beluga ring-slate-700/50'}`}>
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
            <div className="min-h-screen bg-nature Xslate-950 text-slate-100 p-6 md:p-10 font-sans selection:bg-emerald-500/30">
                {/* Header section */}
                <header className="mb-8">
                    <div className="flex items-center gap-3">
                        <span className="text-3xl hidden">🔄</span>
                        <h1 className="text-2xl md:text-3xl font-bold tracking-tight text-white">Mill Edit Submission Differences</h1>
                    </div>
                    <p className="mt-2 text-sm md:text-base Xtext-slate-400 text-white">
                        Compare the Mill's current information to user-submitted changes.
                    </p>
                </header>

                {/* Visual Diff Table */}
                <main className="space-y-10">

                    {/* Diff */}                    
                    <section>
                        <div className="overflow-x-auto rounded-xl border Xborder-slate-800 Xbg-slate-900 shadow-xl bg-velvet border-velvet">
                            <table className="w-full border-collapse text-left text-sm">
                                <thead>
                                    <tr className="border-b Xborder-slate-800 Xbg-slate-900 Xtext-slate-400 border-aircraft bg-velvet text-beluga font-semibold">
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
                                                className={`transition-colors duration-150 odd:bg-aircraft even:bg-coupe ${
                                                    changed ? 'bg-emerald-950/20 hover:bg-emerald-950/30' : 'hover:bg-slate-800/40 opacity-60'
                                                }`}
                                            >
                                                {/* Field Name */}
                                                <td className="p-4 font-semibold Xtext-slate-300 antialiased flex items-center gap-2">
                                                    <span className="font-mono Xtext-slate-400 text-beluga">{key}</span>
                                                    {changed && (
                                                        <span className="inline-flex items-center rounded-full bg-emerald-400/10 px-1.5 py-0.5 text-xs font-medium text-emerald-400 ring-1 ring-emerald-400/20" title="Data Changed">
                                                            Modified
                                                        </span>
                                                    )}
                                                </td>

                                                {/* Original DB State */}
                                                <td className={`p-4 font-mono Xtext-slate-300 text-beluga ${changed ? 'opacity-60' : ''}`}>
                                                    {renderValue(value)}
                                                    {/* {renderValue(original[key])} */}
                                                </td>

                                                {/* Incoming Form Submission */}
                                                <td className={`p-4 font-mono ${changed ? 'text-emerald-400 font-bold' : 'Xtext-slate-300'}`}>
                                                    {/* I see, it uses 'value', which comes from whichever version we loop over. */}
                                                    {renderValue(submitted[key], changed)}
                                                    {/* {renderValue(value)} */}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    </section>

                    {/**
                     * Approve and Reject buttons!
                     */}
                    <section>
                        <h2
                            className="text-2xl mb-5 hidden"
                        >What do you want to do?</h2>
                        <div className="flex flex-row w-full space-x-8 justify-center items-center px-10">
                            <Button
                                className="bg-red-600 text-white font-bold text-xl px-6 py-8 hover:text-red-500"
                                asChild
                            >
                                <Link
                                    href={reject(rejectHash)}
                                    className=""
                                    method="post"
                                    as="button"
                                >
                                    Reject Changes
                                    <ThumbsDown 
                                        data-icon="inline-end"
                                        size={40}
                                        className="w-8 h-8 size-8 ml-2"
                                    />
                                </Link>                                
                            </Button>

                            <Button
                                className="bg-green-600 text-white font-bold text-xl px-6 py-8 hover:text-green-500"
                                asChild
                            >
                                <Link
                                    href={approve(approveHash)}
                                    className=""
                                    method="post"
                                    as="button"
                                >
                                    Approve Updates
                                    <ThumbsUp 
                                        data-icon="inline-end"
                                        size={40}
                                        className="w-8 h-8 size-8 ml-2"
                                    />
                                </Link>                                
                            </Button>
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
                    {showRaw &&
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
                    }
                </main>
            </div>
        </AppLayout>
    );
}
