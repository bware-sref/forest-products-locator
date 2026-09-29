import React, {
    useEffect,
    useRef,
    useState
} from 'react';
import {
  countDecimals
} from "@/lib/utils";


interface AnimatedCounterProps {
  targetNumber: number;
  duration?: number; // Animation duration in milliseconds
  className?: string;
}

export const AnimatedCounter: React.FC<AnimatedCounterProps> = ({
  targetNumber,
  duration = 3000,
  className = "",
}) => {
  const [count, setCount] = useState<number>(0);
  const elementRef = useRef<HTMLSpanElement | null>(null);
  const hasAnimated = useRef<boolean>(false);  
  const numDecimals = countDecimals(targetNumber);

  useEffect(() => {
    const observerOptions = {
      root: null, // uses the viewport
      rootMargin: '0px',
      threshold: 0.1, // triggers when 10% of the element is visible
    };

    const observer = new IntersectionObserver(([entry]) => {
      // Trigger only once when it intersects the viewport
      if (entry.isIntersecting && !hasAnimated.current) {
        hasAnimated.current = true;
        animateNumber();
        // Optional: stop observing once animation triggers
        if (elementRef.current) {
          observer.unobserve(elementRef.current);
        }
      }
    }, observerOptions);

    if (elementRef.current) {
      observer.observe(elementRef.current);
    }

    // Clean up observer on component unmount
    return () => {
      if (elementRef.current) {
        observer.disconnect();
      }
    };
  }, [targetNumber, duration]);

  const animateNumber = () => {
    let startTimestamp: number | null = null;
    const startValue = 0;
    const step = (timestamp: number) => {
      if (!startTimestamp) startTimestamp = timestamp;
      const progress = Math.min((timestamp - startTimestamp) / duration, 1);
      // Calculate current value (linear progression)
      const intermediateValue = (progress * (targetNumber - startValue) + startValue);
      // if the original number has decimals, format currentValue accordingly
      const currentValue = numDecimals ? 
        parseFloat(intermediateValue.toFixed(numDecimals))
        : 
        Math.floor(intermediateValue);
      setCount(currentValue);

      if (progress < 1) {
        window.requestAnimationFrame(step);
      }
    };

    window.requestAnimationFrame(step);
  };

  return (
    <span ref={elementRef} className={className}>
      {count.toLocaleString()}
    </span>
  );
};
