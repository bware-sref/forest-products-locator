
export async function fetchMills(url, params) {
    // const text = params.text || null;
    // const state = params.state || null;
    // const county = params.county || null;
    // const millTypes = params.millTypes || null;
    // const woodSpecies = params.woodSpecies || null;
    const urlParams = new URLSearchParams(params);
    console.log(urlParams.toString());   
    try {
        const response = await fetch(url + '?' + urlParams.toString());
        if (!response.ok) {
            throw new Error(`Response status: ${response.status}`);
        }
        const result = await response.json();
        // console.log(result);
        return result;
    } catch (error) {
        console.log(`Error with fetch: ${error.message}`);
    }
}