// --- Importações ---
import { Component, Input, OnInit, OnDestroy } from '@angular/core'; // <-- CORREÇÃO: Importado OnInit, OnDestroy
import { CommonModule } from '@angular/common';
import { Router, RouterLink } from '@angular/router'; // <-- CORREÇÃO: Importado Router e RouterLink
import { Observable, Subscription } from 'rxjs';
import { TenantService, TenantTheme } from '../../services/tenant.service';
import { Product } from '../../models/product.model';
import { environment } from '../../../environments/environment';
import { FavoriteService } from '../../services/favorite.service'; // <-- CORREÇÃO: Importado o FavoriteService

@Component({
  selector: 'app-product-card',
  standalone: true,
  imports: [
    CommonModule,
  ],
  templateUrl: './product-card.component.html',
  styleUrls: ['./product-card.component.scss']
})
export class ProductCardComponent implements OnInit, OnDestroy {
  @Input() product: Product | null = null;

  theme$: Observable<TenantTheme | null>;
  apiImage: string = environment.imageDB;

  isFavorite = false;

  // private productSub?: Subscription; // <-- CORREÇÃO: Removido, 'product' não é um Observable
  private favoriteSub?: Subscription;

  constructor(
    private tenantService: TenantService,
    private favoriteService: FavoriteService, // <-- CORREÇÃO: Injetado o FavoriteService
    private router: Router, // <-- CORREÇÃO: Injetado o Router
  ) {
    this.theme$ = this.tenantService.tenantTheme$;
  }

  ngOnInit(): void {
    // CORREÇÃO: A lógica para verificar favoritos foi totalmente reescrita.
    // O @Input 'product' não é um Observable, então não podemos usar .subscribe() nele.
    // Nós apenas verificamos se ele existe e, então, nos inscrevemos no FavoriteService.

    if (this.product) { // 1. Garante que o produto foi recebido
      // 2. Ouve as mudanças de favorito PARA ESTE produto
      this.favoriteSub = this.favoriteService.getFavorites().subscribe(favs => {
        // 3. Atualiza o status 'isFavorite' baseado na lista de IDs
        this.isFavorite = favs.includes(this.product!.id); // Usar '!' é seguro aqui
      });
    }
  }

  ngOnDestroy(): void {
    // this.productSub?.unsubscribe(); // <-- CORREÇÃO: Removido
    this.favoriteSub?.unsubscribe(); // <-- CORRETO: Limpa a inscrição do favoriteService
  }

  // --- Métodos de Template (Estes parecem corretos) ---

  getHoverClass(theme: TenantTheme | null): string {
    if (!theme || !theme.hover_effect || theme.hover_effect.name === 'none') {
      return '';
    }
    return `hover-effect-${theme.hover_effect.name}`;
  }

  getButtonHoverClass(theme: TenantTheme | null): string {
    if (!theme || !theme.hover_effect || theme.hover_effect.name === 'none') {
      return '';
    }
    return theme.hover_effect.name === 'default'
      ? 'hover-effect-default-button'
      : `hover-effect-${theme.hover_effect.name}`;
  }

  // --- Navegação ---

  viewProductDetails(): void {
    // CORREÇÃO: Substituído 'window.location.href' pelo 'router.navigate'
    // Isso evita que a aplicação Angular recarregue inteiramente.
    if (this.product) {
      this.router.navigate(['/product', this.product.id]);
    }
  }

  // --- Ações do Card (Exemplo) ---

  // Você provavelmente vai querer adicionar uma função para favoritar
  toggleFavorite(event: MouseEvent): void {
    event.stopPropagation(); // Impede que o clique dispare a navegação do card
    if (this.product) {
      this.favoriteService.toggleFavorite(this.product.id);
    }
  }
}
