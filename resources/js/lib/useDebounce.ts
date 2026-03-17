import {
    useEffect,
    useRef,
    useMemo,
} from 'react';
import { debounce } from 'lodash-es';

/**
 * Returns a function which invokes callback after delay milliseconds
 * @param callback 
 * @param delay 
 * @returns 
 */
export default function useDebounce(callback: () => void, delay: number = 500) {
    // es-lint change 'any' to 'unknown'
    const ref = useRef<unknown>(null);

    useEffect(() => {
        // es-lint hates this because not spozta access refs during render
        // doing so may cause strange render behaviors
        // the docs actually say, "can cause your component not to update as expected."
        // But if we expect the issue might manifest, does that make the docs a liar?
        ref.current = callback;
    }, [callback]);

    const debouncedCallback = useMemo(() => {
        const func = () => {
            if (ref.current && typeof ref.current == 'function') {
                ref.current?.();
            }
        };
        return debounce(func, delay);
        // es-lint says 'delay' is a dependency and either add the dependency or remove the dependency array.
        // I wonder how it will react to the latter...
    }, []);

    return debouncedCallback;
}