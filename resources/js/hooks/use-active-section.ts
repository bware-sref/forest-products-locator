import { useEffect, useState } from 'react';

/**
 * Scrollspy: returns the id of the section the reader is currently in.
 * The active section is the last one whose heading has scrolled above `threshold` px from the top
 * of the viewport. Once the page can't scroll any further, the last section wins, so a short final
 * section can still become active even though its heading never reaches the threshold.
 */
export function useActiveSection(ids: string[], threshold = 120): string | undefined {
    const [active, setActive] = useState<string | undefined>(ids[0]);
    // a string key keeps the effect from re-running when the caller passes a new array each render
    const key = ids.join('\n');

    useEffect(() => {
        const sectionIds = key ? key.split('\n') : [];
        let frame = 0;

        const update = () => {
            frame = 0;
            const root = document.documentElement;
            const atBottom = window.innerHeight + window.scrollY >= root.scrollHeight - 2;
            let current = sectionIds[0];

            if (atBottom) {
                current = sectionIds[sectionIds.length - 1];
            } else {
                for (const id of sectionIds) {
                    const el = document.getElementById(id);
                    if (el && el.getBoundingClientRect().top <= threshold) {
                        current = id;
                    }
                }
            }

            setActive(current);
        };

        const schedule = () => {
            if (!frame) {
                frame = window.requestAnimationFrame(update);
            }
        };

        update();
        window.addEventListener('scroll', schedule, { passive: true });
        window.addEventListener('resize', schedule);

        return () => {
            window.removeEventListener('scroll', schedule);
            window.removeEventListener('resize', schedule);
            window.cancelAnimationFrame(frame);
        };
    }, [key, threshold]);

    return active;
}
