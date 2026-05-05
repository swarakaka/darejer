// Minimal PHP-style date formatter for tokens we expose via the
// `format()` setter on Darejer date components. Add tokens here as needed.
const MONTHS_FULL = [
  'January',
  'February',
  'March',
  'April',
  'May',
  'June',
  'July',
  'August',
  'September',
  'October',
  'November',
  'December',
]
const MONTHS_SHORT = MONTHS_FULL.map((m) => m.slice(0, 3))
const DAYS_FULL = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']
const DAYS_SHORT = DAYS_FULL.map((d) => d.slice(0, 3))

const TOKEN_RE = /\\.|Y|y|m|n|d|j|F|M|l|D/g

export function formatPhpDate(date: Date, format: string): string {
  return format.replace(TOKEN_RE, (token) => {
    if (token.startsWith('\\')) return token.slice(1)
    switch (token) {
      case 'Y':
        return String(date.getFullYear())
      case 'y':
        return String(date.getFullYear()).slice(-2)
      case 'm':
        return String(date.getMonth() + 1).padStart(2, '0')
      case 'n':
        return String(date.getMonth() + 1)
      case 'd':
        return String(date.getDate()).padStart(2, '0')
      case 'j':
        return String(date.getDate())
      case 'F':
        return MONTHS_FULL[date.getMonth()]
      case 'M':
        return MONTHS_SHORT[date.getMonth()]
      case 'l':
        return DAYS_FULL[date.getDay()]
      case 'D':
        return DAYS_SHORT[date.getDay()]
      default:
        return token
    }
  })
}
