import { createStore, withProps } from '@ngneat/elf';
import { localStorageStrategy, persistState } from '@ngneat/elf-persist-state';
import { map } from 'rxjs';

export interface ShippingInfo {
  id: number;
  name: string;
  price: number;
  delivery_time: number;
}

export interface ShippingState {
  cep: string | null;
  shippingOption: ShippingInfo | null;
}

const initialState: ShippingState = {
  cep: null,
  shippingOption: null,
};

export const shippingStore = createStore(
  { name: 'shipping' },
  withProps<ShippingState>(initialState)
);

persistState(shippingStore, {
  key: 'vibe-vault-shipping',
  storage: localStorageStrategy,
  source: () => shippingStore.pipe(map((state) => ({ cep: state.cep }))),
});

export const cep$ = shippingStore.pipe(map((state) => state.cep));
export const shippingOption$ = shippingStore.pipe(
  map((state) => state.shippingOption)
);

export function setShippingCep(cep: string) {
  shippingStore.update((state) => ({
    ...state,
    cep,
    shippingOption: null,
  }));
}

export function setShippingOption(option: ShippingInfo | null) {
  shippingStore.update((state) => ({
    ...state,
    shippingOption: option,
  }));
}
