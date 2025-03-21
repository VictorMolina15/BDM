// Carga ajustes de fuente y color desde localStorage
var root = document.querySelector(':root');
const loadSettings = () => {
    const settings = JSON.parse(localStorage.getItem('settings'));

    if (settings) {
        document.querySelector('html').style.fontSize = settings.fontSize;
        root.style.setProperty('--primary-color-hue', settings.primaryHue);
        root.style.setProperty('--light-color-lightness', settings.lightColorLightness);
        root.style.setProperty('--white-color-lightness', settings.whiteColorLightness);
        root.style.setProperty('--dark-color-lightness', settings.darkColorLightness);
    }
};

// Llama a la función loadSettings cuando la página se carga
window.addEventListener('load', loadSettings);