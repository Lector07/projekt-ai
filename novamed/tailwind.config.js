/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './resources/js/**/*.{vue,js,ts,jsx,tsx}',
        './resources/views/**/*.blade.php',
    ],
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                // Istniejąca konfiguracja sidebara
                sidebar: {
                    DEFAULT: '#001f54',
                    primary: '#001f54',
                    secondary: '#002b6e',
                    foreground: '#ffffff',
                    'primary-foreground': '#ffffff',
                    border: 'rgba(255, 255, 255, 0.2)',
                    accent: 'rgba(255, 255, 255, 0.1)',
                    'accent-foreground': '#ffffff',
                    'dark:DEFAULT': 'hsl(240 10% 4%)',
                    'dark:primary': 'hsl(240 10% 4%)',
                    'dark:border': 'hsl(240 4% 16%)',
                },
                // Nowa paleta kolorów
                nova: {
                    darkest: 'hsl(225 83% 10%)',    // ciemny granat
                    dark: 'hsl(219 100% 16%)',      // granatowy
                    primary: 'hsl(209 95% 24%)',    // niebieski
                    accent: 'hsl(193 49% 47%)',     // turkusowy
                    light: 'hsl(30 75% 99%)',       // biały
                }
            }
        }
    },
    plugins: [],
}
