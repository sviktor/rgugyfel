import defaultTheme from 'tailwindcss/defaultTheme';

/**
 * Tailwind config - Royal Telekom Ügyfélkapu
 *
 * Same brand tokens as rgsite/rgtelekom (shared design system),
 * referenced by the portal CSS variables via design/colors_and_type.css.
 *
 * @type {import('tailwindcss').Config}
 */
export default {
	content: [
		'./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
		'./storage/framework/views/*.php',
		'./resources/**/*.blade.php',
		'./resources/**/*.js',
	],
	theme: {
		extend: {
			colors: {
				navy:   { 50: '#ecf1f7', 100: '#d6e1ec', 300: '#8aa8c8', 500: '#3a6a98', 600: '#25527d', 700: '#173a5f', 800: '#0E2A47', 900: '#07182B' },
				gold:   { 50: '#FAF3E0', 100: '#F3E6C6', 300: '#E2C893', 500: '#CAA868', 600: '#B8924A', 700: '#A38241', 800: '#8E6E36', 900: '#6F5326' },
				cream:  '#F6F1E7',
				ivory:  '#FBF8F2',
				paper:  '#FFFFFF',
			},
			fontFamily: {
				display: ['"Cormorant Garamond"', 'Garamond', '"Times New Roman"', 'serif'],
				sans:    ['Inter', ...defaultTheme.fontFamily.sans],
				mono:    ['ui-monospace', '"SF Mono"', 'Menlo', 'monospace'],
			},
			maxWidth: { container: '1240px' },
		},
	},
	plugins: [],
};
