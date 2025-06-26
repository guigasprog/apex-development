import { createStore, withProps } from '@ngneat/elf';
import { jwtDecode } from 'jwt-decode';

export interface AuthState {
  token: string | null;
  user: any | null;
  isLoggedIn: boolean;
}

const getInitialState = (): AuthState => {
  const token = localStorage.getItem('vibe_vault_token');
  if (token) {
    try {
      return {
        token,
        user: jwtDecode(token),
        isLoggedIn: true,
      };
    } catch (e) {
      localStorage.removeItem('vibe_vault_token');
    }
  }
  return { token: null, user: null, isLoggedIn: false };
};

export const authStore = createStore(
  { name: 'auth' },
  withProps<AuthState>(getInitialState())
);

export function setToken(token: string | null) {
  if (token) {
    localStorage.setItem('vibe_vault_token', token);
    authStore.update((state) => ({
      ...state,
      token,
      user: jwtDecode(token),
      isLoggedIn: true,
    }));
  } else {
    localStorage.removeItem('vibe_vault_token');
    authStore.update((state) => ({
      ...state,
      token: null,
      user: null,
      isLoggedIn: false,
    }));
  }
}

export function logout() {
  setToken(null);
}
