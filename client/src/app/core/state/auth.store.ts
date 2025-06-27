import { createStore, withProps } from '@ngneat/elf';
import { localStorageStrategy, persistState } from '@ngneat/elf-persist-state';
import { jwtDecode } from 'jwt-decode';

export interface User {
  id: number;
  nome: string;
  email: string;
}

export interface AuthState {
  token: string | null;
  user: User | null;
  isLoggedIn: boolean;
}

const getInitialState = (): AuthState => {
  const token = localStorage.getItem('vibe_vault_token');
  const userJson = localStorage.getItem('vibe_vault_user');

  if (token && userJson) {
    try {
      const decodedToken: { exp: number } = jwtDecode(token);
      if (decodedToken.exp * 1000 > Date.now()) {
        return {
          token,
          user: JSON.parse(userJson),
          isLoggedIn: true,
        };
      }
    } catch (e) {
      localStorage.removeItem('vibe_vault_token');
      localStorage.removeItem('vibe_vault_user');
    }
  }

  return { token: null, user: null, isLoggedIn: false };
};

export const authStore = createStore(
  { name: 'auth' },
  withProps<AuthState>(getInitialState())
);

persistState(authStore, {
  key: 'vibe-vault-auth',
  storage: localStorageStrategy,
});

export function login(user: User, token: string) {
  localStorage.setItem('vibe_vault_token', token);
  localStorage.setItem('vibe_vault_user', JSON.stringify(user));
  authStore.update((state) => ({
    ...state,
    token,
    user,
    isLoggedIn: true,
  }));
}

export function logout() {
  localStorage.removeItem('vibe_vault_token');
  localStorage.removeItem('vibe_vault_user');
  authStore.update((state) => ({
    ...state,
    token: null,
    user: null,
    isLoggedIn: false,
  }));
}
