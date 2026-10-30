import js from '@eslint/js';
import prettier from 'eslint-config-prettier/flat';
import react from 'eslint-plugin-react';
import reactHooks from 'eslint-plugin-react-hooks';
import globals from 'globals';
import typescript from 'typescript-eslint';
import { reactRefresh } from 'eslint-plugin-react-refresh';
import jsxA11y from 'eslint-plugin-jsx-a11y';

/** @type {import('eslint').Linter.Config[]} */
export default [
    js.configs.recommended,
    reactHooks.configs.flat.recommended,
    ...typescript.configs.recommended,
    {
        ...jsxA11y.flatConfigs.recommended,
        settings: {
            /**
             * Check our wrapper components as the elements they render, or jsx-a11y skips them
             * (e.g. a <Button> with only an icon inside would otherwise go unflagged).
             */
            'jsx-a11y': {
                components: {
                    Button: 'button',
                    Input: 'input',
                    Label: 'label',
                    Textarea: 'textarea',
                    Link: 'a',
                },
            },
        },
    },
    {
        ...react.configs.flat.recommended,
        ...react.configs.flat['jsx-runtime'], // Required for React 17+
        languageOptions: {
            globals: {
                ...globals.browser,
            },
        },
        plugins: {
            'react-refresh': reactRefresh.plugin,
        },
        rules: {
            'react/react-in-jsx-scope': 'off',
            'react/prop-types': 'off',
            'react/no-unescaped-entities': 'off',
            'react-refresh/only-export-components': [
                'warn',
                {
                    allowConstantExport: true,
                }
            ],
        },
        settings: {
            react: {
                version: 'detect',
            },
        },
    },
    {
        ignores: [
            '.idea',
            'vendor',
            'node_modules',
            'public',
            'bootstrap/ssr',
            'tailwind.config.js',
            'resources/js/mill-map.js',
            'resources/js/us-state-polygon-coords.js',
            // don't lint Laravel's mess
            'resources/js/actions',
            // same for Backpack and Basset
            'storage/app/public/basset',
        ],
    },
    prettier, // Turn off all rules that might conflict with Prettier
];
