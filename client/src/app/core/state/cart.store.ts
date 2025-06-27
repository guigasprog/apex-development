import { createStore } from '@ngneat/elf';
import {
  withEntities,
  selectAllEntities,
  selectEntities,
  setEntities,
  addEntities,
  updateEntities,
  deleteEntities,
} from '@ngneat/elf-entities';
import { localStorageStrategy, persistState } from '@ngneat/elf-persist-state';
import { map } from 'rxjs/operators';

export interface CartItem {
  id: number;
  name: string;
  price: number;
  image_url: string;
  quantity: number;
}

export const cartStore = createStore(
  { name: 'cart' },
  withEntities<CartItem>()
);

persistState(cartStore, {
  key: 'vibe-vault-cart',
  storage: localStorageStrategy,
});

// Selectors (para ler os dados do carrinho)
export const cartItems$ = cartStore.pipe(selectAllEntities());
export const cartEntities$ = cartStore.pipe(selectEntities());

export const cartCount$ = cartStore.pipe(
  selectAllEntities(),
  map((items) => items.reduce((acc, item) => acc + item.quantity, 0))
);

export const cartTotal$ = cartStore.pipe(
  selectAllEntities(),
  map((items) =>
    items.reduce((acc, item) => acc + item.price * item.quantity, 0)
  )
);

export function addItemToCart(item: CartItem) {
  const existingItem = cartStore.getValue().entities[item.id];

  if (existingItem) {
    updateCartItemQuantity(item.id, existingItem.quantity + item.quantity);
  } else {
    cartStore.update(addEntities(item));
  }
}

export function updateCartItemQuantity(itemId: number, quantity: number) {
  if (quantity > 0) {
    cartStore.update(updateEntities(itemId, { quantity }));
  } else {
    removeCartItem(itemId);
  }
}

export function removeCartItem(itemId: number) {
  cartStore.update(deleteEntities(itemId));
}

export function clearCart() {
  cartStore.update(setEntities([]));
}
