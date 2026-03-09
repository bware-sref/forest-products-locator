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
    const ref = useRef<any>(null);

    useEffect(() => {
        ref.current = callback;
    }, [callback]);

    const debouncedCallback = useMemo(() => {
        const func = () => {
            ref.current?.();
        };
        return debounce(func, delay);
    }, []);

    return debouncedCallback;
}