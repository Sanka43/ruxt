export interface ThemePreset {
  id: string;
  label: string;
  accentBg: string;
  orbA: string;
  orbB: string;
  vars: Record<string, string>;
}

export const THEME_PRESETS: Record<string, ThemePreset> = {
  ruxt: {
    id: 'ruxt',
    label: 'RuXt Green',
    accentBg: '#143228',
    orbA: '#143228',
    orbB: '#1a2330',
    vars: {
      '--bg': '#eef1f5',
      '--surface': '#ffffff',
      '--ink': '#161a22',
      '--muted': '#68707c',
      '--line': '#e4e7ec',
      '--accent': '#143228',
      '--accent-soft': '#e7efeb',
      '--ruxt': '#143228',
      '--ruxt-soft': '#e7efeb',
      '--sidebar': '#1a2330',
      '--sidebar-line': '#2a3545',
      '--sidebar-text': '#d7dee8',
      '--sidebar-muted': '#8b98ab',
      '--sidebar-panel': '#121925',
      '--sidebar-hover': '#243041',
      '--sidebar-active': '#2b3a4d'
    }
  },
  light: {
    id: 'light',
    label: 'Clean Light',
    accentBg: '#1967ff',
    orbA: '#1967ff',
    orbB: '#2a69ac',
    vars: {
      '--bg': '#f7f8fa',
      '--surface': '#ffffff',
      '--ink': '#17191d',
      '--muted': '#68707c',
      '--line': '#e4e7ec',
      '--accent': '#1967ff',
      '--accent-soft': '#eaf1ff',
      '--ruxt': '#1967ff',
      '--ruxt-soft': '#eaf1ff',
      '--sidebar': '#ffffff',
      '--sidebar-line': '#e4e7ec',
      '--sidebar-text': '#424954',
      '--sidebar-muted': '#8a929e',
      '--sidebar-panel': '#f8fafc',
      '--sidebar-hover': '#f1f5f9',
      '--sidebar-active': '#e2e8f0'
    }
  },
  dark: {
    id: 'dark',
    label: 'Focus Dark',
    accentBg: '#7eb6ff',
    orbA: '#1d2a3d',
    orbB: '#0f141c',
    vars: {
      '--bg': '#0f141c',
      '--surface': '#171e29',
      '--ink': '#e8eef7',
      '--muted': '#9aa7b8',
      '--line': '#2a3545',
      '--accent': '#7eb6ff',
      '--accent-soft': '#1d2a3d',
      '--ruxt': '#7eb6ff',
      '--ruxt-soft': '#1d2a3d',
      '--sidebar': '#0b1017',
      '--sidebar-line': '#243041',
      '--sidebar-text': '#d7dee8',
      '--sidebar-muted': '#8b98ab',
      '--sidebar-panel': '#121822',
      '--sidebar-hover': '#1e2838',
      '--sidebar-active': '#263448'
    }
  },
  ocean: {
    id: 'ocean',
    label: 'Ocean Calm',
    accentBg: '#0f6f8a',
    orbA: '#0f6f8a',
    orbB: '#123741',
    vars: {
      '--bg': '#eef5f7',
      '--surface': '#ffffff',
      '--ink': '#14303a',
      '--muted': '#5f7580',
      '--line': '#d5e3e8',
      '--accent': '#0f6f8a',
      '--accent-soft': '#e3f3f7',
      '--ruxt': '#0f6f8a',
      '--ruxt-soft': '#e3f3f7',
      '--sidebar': '#123741',
      '--sidebar-line': '#1f4b57',
      '--sidebar-text': '#d7eef3',
      '--sidebar-muted': '#8ab8c4',
      '--sidebar-panel': '#0c262e',
      '--sidebar-hover': '#1b4a57',
      '--sidebar-active': '#245a6a'
    }
  },
  sand: {
    id: 'sand',
    label: 'Warm Sand',
    accentBg: '#8b5a2b',
    orbA: '#8b5a2b',
    orbB: '#3b2a1c',
    vars: {
      '--bg': '#f6f1ea',
      '--surface': '#fffdf9',
      '--ink': '#2a2118',
      '--muted': '#7a6a58',
      '--line': '#e4d6c4',
      '--accent': '#8b5a2b',
      '--accent-soft': '#f3e8da',
      '--ruxt': '#8b5a2b',
      '--ruxt-soft': '#f3e8da',
      '--sidebar': '#3b2a1c',
      '--sidebar-line': '#523a27',
      '--sidebar-text': '#f4efe6',
      '--sidebar-muted': '#c2b09a',
      '--sidebar-panel': '#2c1e13',
      '--sidebar-hover': '#4e3725',
      '--sidebar-active': '#5e432d'
    }
  }
};

export function applyTheme(themeId: string) {
  const preset = THEME_PRESETS[themeId] || THEME_PRESETS.ruxt;
  const root = document.documentElement;
  Object.entries(preset.vars).forEach(([key, value]) => {
    root.style.setProperty(key, value);
  });
  localStorage.setItem('ruxt_active_theme', preset.id);
}

export function initTheme(): string {
  const saved = localStorage.getItem('ruxt_active_theme') || 'ruxt';
  applyTheme(saved);
  return saved;
}
