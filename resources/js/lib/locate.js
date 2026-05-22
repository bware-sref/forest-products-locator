// crude locator tool

export const locate = (defaultCoordinates) => {
    if (! ('geolocation' in navigator)) {
        console.warn('geolocation is not in navigator: ', navigator);
        // default coordinates?
        return defaultCoordinates;
    }

    navigator.geolocation.getCurrentPosition((position) => {
        console.log('in locate(), currentPosition: ', position);
        return [position.coords.latitude, position.coords.longitude];
    },
    (error) => {
        console.error('error with geolocation: ', error);
        return defaultCoordinates;
    });
}

