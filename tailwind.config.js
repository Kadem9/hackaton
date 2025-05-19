const plugin = require('tailwindcss/plugin')

/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./templates/**/*.html.twig",
  ],
  darkMode: 'class',
  safelist: [
    'text-red',
    '.icon-sm',
    '.alert-success',
    '.alert-error',
    '.alert-info',
    '.alert-close',
  ],
  theme: {
    //colors
    extend: {
      colors: {
        'color': 'var(--color)',
        'color-light': 'var(--color-light)',
        'bg': 'var(--bg)',
        'bg-light': 'var(--bg-light)',
        'border' : 'var(--border)',
        'shadow' : 'var(--shadow)',
        'transparent' : 'transparent',
        'contrast' : 'var(--contrast)',
        'contrast-hover' : 'var(--contrast-hover)',
        'contrast-opacity' : 'var(--contrast-opacity)',
        'grey' : 'var(--grey)',
        //front
        'front-bg': 'var(--front-bg)',
        'front-primary': 'var(--front-primary)',
        'front-secondary': 'var(--front-secondary)',
        'front-tertiary': 'var(--front-tertiary)',
        'front-red': 'var(--front-red)',
        'front-green': 'var(--front-green)',
        'front-black': 'var(--front-black)',
      },
      gridTemplateColumns: {
        '260-1' : '260px 1fr',
      },
      gridTemplateRows: {
        '60-1' : '60px 1fr',
      },
      //spacing
      spacing: {
        '1' : 'var(--space)',
        '2' : 'calc(2 * var(--space))',
        '25' : 'calc(2.5 * var(--space))',
        '3' : 'calc(3 * var(--space))',
        '4' : 'calc(4 * var(--space))',
        '5' : 'calc(5 * var(--space))',
        '6' : 'calc(6 * var(--space))',
        '7' : 'calc(7 * var(--space))',
        '8' : 'calc(8 * var(--space))',
      },
      //transition
      transitionProperty: {
        'dashboard': 'grid-template-columns',
        'width': 'width',
        'background-color' : 'background-color'
      },
    },
  },
  plugins: [
    require('@savvywombat/tailwindcss-grid-areas'),
    plugin(function({ addComponents }) {
      addComponents({
        //dashboard
        '.app-dashboard.close': {
          gridTemplateColumns: '20px 1fr',
        },
        '.app-dashboard.close .app-sidebar__logo .brand': {
          opacity: '0',
          visibility: 'hidden',
          transition: 'visibility 0s linear 100ms,opacity 100ms linear',
        },
        '.app-dashboard.close .app-sidebar__logo .app-sidebar__btn': {
          opacity: '1',
          visibility: 'visible',
          right: '-12px',
          transition: 'right 300ms linear',
        },
        '.app-dashboard.close .app-sidebar__logo .app-sidebar__btn svg': {
          transform: 'rotate(180deg)',
          marginRight: '-1px',
        },
        '.app-dashboard.close .app-sidebar__menu': {
          opacity: '0',
          visibility: 'hidden',
          transition: 'visibility 0s linear 300ms,opacity 300ms linear',
        },
        '.app-dashboard.close .theme-switcher': {
          opacity: '0',
          visibility: 'hidden',
          transition: 'visibility 0s linear 100ms,opacity 100ms linear',
        },
        '.app-sidebar:hover .app-sidebar__logo .app-sidebar__btn': {
          opacity: '1',
          visibility: 'visible',
          transitionDelay: '0s',
        },
        '.app-sidebar .app-sidebar__logo .brand': {
          opacity: '1',
          visibility: 'visible',
          transitionDelay: '0s',
        },
        '.app-sidebar .app-sidebar__logo .app-sidebar__btn': {
          position: 'absolute',
          background: 'var(--bg-light)',
          display: 'flex',
          justifyContent: 'center',
          alignItems: 'center',
          top: 'auto',
          right: '-33px',
          boxShadow: 'rgb(9 30 66 / 8%) 0px 0px 0px 1px, rgb(9 30 66 / 8%) 0px 2px 4px 1px',
          borderRadius: '50%',
          height: '25px',
          width: '25px',
          opacity: '0',
          visibility: 'hidden',
          transition: 'visibility 0s linear 300ms,opacity 300ms linear, right 300ms linear',
          zIndex: '2',
        },
        '.theme-dark .app-sidebar .app-sidebar__logo .app-sidebar__btn': {
          color: 'white',
          backgroundColor: 'var(--contrast) !important',
        },
        '.app-sidebar .app-sidebar__logo .app-sidebar__btn button': {
          display: 'flex',
          justifyContent: 'center',
          alignItems: 'center',
          height: '100%',
          width: '100%',
        },
        '.app-sidebar .app-sidebar__logo .app-sidebar__btn button svg': {
          width: '12px',
          height: '12px',
          marginRight: '1px',
          marginBottom: '1px',
        },
        '.app-sidebar .app-sidebar__menu': {
          overflow: 'hidden',
          opacity: '1',
          visibility: 'visible',
          transitionDelay: '0s',
        },
        '.app-sidebar .app-sidebar__menu svg': {
          minWidth: '16px',
          minHeight: '16px',
        },
        '.app-sidebar .app-sidebar__menu .app-sidebar__item.active a::before': {
          content: '""',
          position: 'absolute',
          width: '5px',
          height: '100%',
          backgroundColor: 'var(--contrast)',
          right: '0',
          top: '0',
        },
      })
    })
  ],
}