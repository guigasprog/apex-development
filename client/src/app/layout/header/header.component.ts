import { Component, inject } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { CommonModule, AsyncPipe } from '@angular/common';
import { FormsModule } from '@angular/forms';
import { CartService } from '../../core/services/cart.service';
import { authStore, logout } from '../../core/state/auth.store';
import { cartCount$ } from '../../core/state/cart.store';
import { map } from 'rxjs';
import { ApiService } from '../../core/services/api.service';

// PrimeNG
import { InputTextModule } from 'primeng/inputtext';
import { ButtonModule } from 'primeng/button';
import { AutoCompleteModule } from 'primeng/autocomplete';

@Component({
  selector: 'app-header',
  standalone: true,
  imports: [
    RouterLink,
    CommonModule,
    AsyncPipe,
    FormsModule,
    InputTextModule,
    ButtonModule,
    AutoCompleteModule,
  ],
  templateUrl: './header.component.html',
})
export class HeaderComponent {
  private cartService = inject(CartService);
  private router = inject(Router);
  private apiService = inject(ApiService);

  isMobileMenuOpen = false;
  isMobileSearchActive = false;

  suggestions: any[] = [];
  id_search?: number;
  selectedProduct: any;

  isLoggedIn$ = authStore.pipe(map((state) => state.isLoggedIn));
  user$ = authStore.pipe(map((state) => state.user));
  cartItemCount$ = cartCount$;

  toggleMobileMenu(): void {
    this.isMobileMenuOpen = !this.isMobileMenuOpen;
  }
  toggleMobileSearch(): void {
    this.isMobileSearchActive = !this.isMobileSearchActive;
  }

  search(event: { query: string }): void {
    this.apiService.products
      .getSearchSuggestions(event.query, this.id_search)
      .then((response) => {
        this.suggestions = response.data.suggestions;
        this.id_search = response.data.id_search;
      })
      .catch((err) => {
        console.error('Erro ao buscar sugestões:', err);
        this.suggestions = [];
      });
  }

  onSuggestionSelect(product: any): void {
    if (product?.id) {
      this.router.navigate(['/product', product.id]);
      this.selectedProduct = null;
      if (this.isMobileSearchActive) {
        this.isMobileSearchActive = false;
      }
    }
  }

  openCart(): void {
    this.cartService.openCart();
  }
  logout(): void {
    logout();
    this.router.navigate(['/']);
  }
}
