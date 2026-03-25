import 'vuetify/styles';
import '@mdi/font/css/materialdesignicons.css';
import { createVuetify } from 'vuetify';
import * as components from 'vuetify/components';
import * as directives from 'vuetify/directives';

const pipelineTheme = {
    dark: true,
    colors: {
        background: '#0d1a14',
        surface: '#132018',
        primary: '#2ecc5e',
        'primary-darken-1': '#27a54e',
        secondary: '#1a2e21',
        'on-background': '#ffffff',
        'on-surface': '#ffffff',
    },
};

export default createVuetify({
    components,
    directives,
    theme: {
        defaultTheme: 'pipelineTheme',
        themes: {
            pipelineTheme,
        },
    },
    defaults: {
        VTextField: {
            variant: 'outlined',
            density: 'comfortable',
            color: 'primary',
        },
        VBtn: {
            variant: 'flat',
        },
    },
});
