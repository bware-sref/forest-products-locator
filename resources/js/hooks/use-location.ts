import { useState, useEffect } from 'react';

export const useLocation = () => {
  const [position, setPosition] = useState<GeolocationPosition | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (!navigator.geolocation) {
      setError("Geolocation is not supported by your browser");
      return;
    }

    const handleSuccess = (pos: GeolocationPosition) => setPosition(pos);
    const handleError = (err: GeolocationPositionError) => setError(err.message);

    const watchId = navigator.geolocation.watchPosition(handleSuccess, handleError);

    return () => navigator.geolocation.clearWatch(watchId);
  }, []);

  return { position, error };
};