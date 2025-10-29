import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { ProductService, ProductDetail, ProductInteraction } from '../../services/product.service';
import { FavoriteService } from '../../services/favorite.service';
import { Observable, Subscription, of } from 'rxjs';
import { switchMap, catchError, tap } from 'rxjs/operators';
import { BuyDialogComponent } from '../../components/buy-dialog/buy-dialog.component';
import { environment } from '../../../environments/environment';
import { TenantService, TenantTheme } from '../../services/tenant.service';
import { ProductGalleryComponent } from '../../components/product-gallery/product-gallery.component';

@Component({
  selector: 'app-product-detail',
  standalone: true,
  imports: [
    CommonModule,
    BuyDialogComponent,
    RouterLink,ProductGalleryComponent
  ],
  templateUrl: './product-detail.component.html',
  styleUrls: ['./product-detail.component.scss']
})
export class ProductDetailComponent implements OnInit, OnDestroy {

  product$: Observable<ProductDetail | null>;
  currentProduct: ProductDetail | null = null;
  apiImage = environment.imageDB

  isFavorite = false;
  isLoading = true;
  error: string | null = null;
  showBuyDialog = false;

  theme$: Observable<TenantTheme | null>;

  private favoriteSub?: Subscription;

  constructor(
    private route: ActivatedRoute,
    private router: Router,
    private productService: ProductService,
    private favoriteService: FavoriteService,
    private tenantService: TenantService
  ) {
    this.theme$ = this.tenantService.tenantTheme$;
    this.product$ = this.route.paramMap.pipe(
      switchMap(params => {
        const id = params.get('id');
        if (!id) {
          this.error = 'Produto não encontrado.';
          this.isLoading = false;
          return of(null);
        }

        this.isLoading = true;
        this.error = null;

        return this.productService.getProductById(Number(id)).pipe(
          tap(product => {
            this.logProductView(product.id);
            this.currentProduct = product;

            this.favoriteSub?.unsubscribe();
            this.favoriteSub = this.favoriteService.getFavorites().subscribe(favs => {
              this.isFavorite = favs.includes(product.id);
            });
          }),
          catchError(err => {
            console.error('Erro ao buscar produto:', err);
            this.error = 'Não foi possível carregar o produto. Tente novamente.';
            this.isLoading = false;
            return of(null);
          })
        );
      }),
      tap(() => this.isLoading = false)
    );
  }

  ngOnInit(): void {
  }

  ngOnDestroy(): void {
    this.favoriteSub?.unsubscribe();
  }

  // --- Ações ---

  toggleFavorite(): void {
    if (this.currentProduct) {
      this.favoriteService.toggleFavorite(this.currentProduct.id);
    }
  }

  openBuyDialog(): void {
    this.showBuyDialog = true;
  }

  handleBuyDecision(decision: 'buyNow' | 'addToCart' | 'close'): void {
    this.showBuyDialog = false;

    if (decision === 'buyNow') {
      console.log('Comprar agora:', this.currentProduct?.id);
    } else if (decision === 'addToCart') {
      console.log('Adicionar ao carrinho:', this.currentProduct?.id);
    }
  }

  // --- Log ---

  private logProductView(productId: number): void {
    const interactionData: ProductInteraction = {
      tipo: 'view',
      produto_id: productId,
      texto_busca: null
    };

    this.productService.logInteraction(interactionData)
      .pipe(
        catchError(err => {
          console.warn('Não foi possível registrar a visualização do produto:', err);
          return of(null);
        })
      )
      .subscribe();
  }

  getHoverClass(theme: TenantTheme | null): string {
    if (!theme || !theme.hover_effect || theme.hover_effect.name === 'none') {
      return '';
    }
    return `hover-effect-${theme.hover_effect.name}`;
  }
}
