import { Component, inject } from '@angular/core';
import { Router, RouterLink } from '@angular/router';
import { CommonModule, AsyncPipe } from '@angular/common';
import { CartService } from '../../core/services/cart.service';
import { authStore, logout, User } from '../../core/state/auth.store';
import { cartCount$ } from '../../core/state/cart.store';
import { map } from 'rxjs';

@Component({
  selector: 'app-header',
  standalone: true,
  imports: [RouterLink, CommonModule, AsyncPipe],
  templateUrl: './header.component.html',
})
export class HeaderComponent {
  private cartService = inject(CartService);
  private router = inject(Router);

  isMobileMenuOpen = false;

  isLoggedIn$ = authStore.pipe(map((state) => state.isLoggedIn));
  user$ = authStore.pipe(map((state) => state.user));
  cartItemCount$ = cartCount$;

  toggleMobileMenu(): void {
    this.isMobileMenuOpen = !this.isMobileMenuOpen;
  }

  openCart(): void {
    this.cartService.openCart();
  }

  logout(): void {
    logout();
    this.router.navigate(['/']);
  }
}
