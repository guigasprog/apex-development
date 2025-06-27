import { Component, inject } from '@angular/core';
import { CommonModule, CurrencyPipe } from '@angular/common';
import { Router, RouterLink } from '@angular/router';
import { FormsModule } from '@angular/forms';
import { CartService } from '../../../core/services/cart.service';
import { authStore } from '../../../core/state/auth.store';
import {
  cartItems$,
  cartStore,
  cartTotal$,
  removeCartItem,
  updateCartItemQuantity,
} from '../../../core/state/cart.store';
import {
  shippingStore,
  setShippingCep,
  setShippingOption,
} from '../../../core/state/shipping.store';
import { ApiService } from '../../../core/services/api.service';
import { MessageService } from 'primeng/api';
import { ToastModule } from 'primeng/toast';

// PrimeNG
import { SidebarModule } from 'primeng/sidebar';
import { ButtonModule } from 'primeng/button';
import { InputTextModule } from 'primeng/inputtext';

@Component({
  selector: 'app-cart',
  standalone: true,
  imports: [
    CommonModule,
    RouterLink,
    CurrencyPipe,
    SidebarModule,
    ButtonModule,
    FormsModule,
    InputTextModule,
    ToastModule,
  ],
  providers: [MessageService],
  templateUrl: './cart.component.html',
})
export class CartComponent {
  cartService = inject(CartService);
  apiService = inject(ApiService);
  router = inject(Router);
  messageService = inject(MessageService);

  cartItems$ = cartItems$;
  cartTotal$ = cartTotal$;

  shippingState$ = shippingStore.pipe();
  isCalculatingShipping = false;

  updateQuantity(event: Event, id: number, newQuantity: number) {
    event.stopPropagation();
    updateCartItemQuantity(id, newQuantity);
  }

  removeItem(event: Event, id: number) {
    event.stopPropagation();
    removeCartItem(id);
  }

  onCepChange(event: any) {
    setShippingCep(event.target.value);
  }

  calculateCartShipping() {
    const state = shippingStore.getValue();
    const items = cartStore.getValue().entities;
    const productList = Object.values(items).map((item) => ({
      id: item.id,
      quantity: item.quantity,
    }));

    if (!state.cep || productList.length === 0) {
      console.warn('CEP ou produtos faltando para o cálculo.');
      return;
    }

    this.isCalculatingShipping = true;
    const payload = {
      to_postal_code: state.cep,
      products: productList,
    };

    this.apiService.shipping
      .calculate(payload)
      .then((response) => {
        const sedexOption = response.data?.[0] ?? null;
        if (sedexOption) {
          setShippingOption({
            ...sedexOption,
            price: parseFloat(sedexOption.price),
          });
        } else {
          console.error('Nenhuma opção de frete SEDEX retornada.');
          setShippingOption(null);
        }
      })
      .catch((err) => {
        console.error('Erro ao calcular frete do carrinho:', err);
        setShippingOption(null);
      })
      .finally(() => (this.isCalculatingShipping = false));
  }

  onVisibleChange(isVisible: boolean) {
    if (!isVisible) {
      this.cartService.closeCart();
    }
  }

  checkout() {
    this.cartService.closeCart(); // Fecha a sidebar

    const isLoggedIn = authStore.getValue().isLoggedIn;

    if (isLoggedIn) {
      // Se estiver logado, navega para a página de checkout real
      this.router.navigate(['/checkout']);
    } else {
      // Se não estiver logado, mostra um alerta e redireciona para o login
      this.messageService.add({
        severity: 'warn',
        summary: 'Login Necessário',
        detail: 'Você precisa fazer login para finalizar a compra.',
      });
      // Salva a rota de checkout para redirecionar após o login
      this.router.navigate(['/login'], {
        queryParams: { redirectUrl: '/checkout' },
      });
    }
  }
}
