import plugin from 'tailwindcss/plugin';

export default plugin(({addComponents, theme}) => {
  addComponents({
    '.timeline': {
      'position': 'relative',
      'padding': '0.5rem',
      'margin': '0',
      '&::before': {
        'content': '""',
        'position': 'absolute',
        'left': '1.75rem',
        'top': '0',
        'bottom': '0',
        'width': '2px',
        'background': 'var(--tw-gray-200)',
      }
    },
    '.timeline-item': {
      'position': 'relative',
      'display': 'flex',
      'gap': theme('spacing.4'),
      'padding': theme('spacing.3'),
      'margin-bottom': theme('spacing.2'),
      'transition': 'all 0.2s ease',
      '&:hover': {
        'transform': 'translateX(4px)'
      },
      '&:last-child': {
        'margin-bottom': '0'
      }
    },
    '.timeline-badge': {
      'flex-shrink': '0',
      'width': '2.75rem',
      'height': '2.75rem',
      'border-radius': '50%',
      'display': 'flex',
      'align-items': 'center',
      'justify-content': 'center',
      'border': '2px solid var(--tw-gray-200)',
      'background': 'var(--tw-light)',
      'z-index': '1',
      'transition': 'all 0.2s ease',
      'i': {
        'font-size': '1.25rem',
        'transition': 'all 0.2s ease'
      }
    },
    '.timeline-content': {
      'flex-grow': '1',
      'background': 'var(--tw-gray-50)',
      'border': '1px solid var(--tw-gray-200)',
      'border-radius': theme('borderRadius.lg'),
      'padding': theme('spacing.4'),
      'transition': 'all 0.2s ease',
      '&:hover': {
        'background': 'var(--tw-light)',
        'border-color': 'var(--tw-gray-300)',
        'box-shadow': theme('boxShadow.sm')
      }
    },
    '.timeline-header': {
      'display': 'flex',
      'justify-content': 'space-between',
      'align-items': 'flex-start',
      'margin-bottom': theme('spacing.2')
    },
    '.timeline-title': {
      'font-weight': theme('fontWeight.medium'),
      'color': 'var(--tw-gray-900)',
      '@apply text-sm': {} // Using apply for font size
    },
    '.timeline-time': {
      '@apply text-xs': {}, // Using apply for font size
      'color': 'var(--tw-gray-500)',
      'white-space': 'nowrap',
      'margin-left': theme('spacing.2')
    },
    '.timeline-description': {
      'color': 'var(--tw-gray-600)',
      '@apply text-sm leading-5': {} // Using apply for font size and line height
    },
    '.timeline-amount': {
      'display': 'inline-flex',
      'align-items': 'center',
      'gap': theme('spacing.1'),
      'font-weight': theme('fontWeight.medium'),
      'color': 'var(--tw-gray-700)',
      'margin-top': theme('spacing.2'),
      '@apply text-sm': {}, // Using apply for font size
      'i': {
        'color': 'var(--tw-gray-400)',
        'font-size': '1rem'
      }
    }
  });
});
