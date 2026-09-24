export function parseDate(value) {
    //formato d/m/Y
    if (value.includes('/')) {
        const [dia, mes, anio] = value.split('/').map(Number);
        return new Date(anio, mes - 1, dia);
    }

    //Y/m/d o formato ISO
    const [anio, mes, dia] = value
        .substring(0, 10)
        .split('-')
        .map(Number);
    
    return new Date(anio, mes - 1, dia);
}