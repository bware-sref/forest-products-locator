
export async function fetchMills(url, params) {
    const urlParams = new URLSearchParams(params);
    try {
        const response = await fetch(url + '?' + urlParams.toString());
        if (!response.ok) {
            throw new Error(`Response status: ${response.status}`);
        }
        const result = await response.json();
        return result;
    } catch (error) {
        console.log(`Error with fetch: ${error.message}`);
    }
}