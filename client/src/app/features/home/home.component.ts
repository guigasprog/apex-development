import { Component, OnDestroy, OnInit, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import { RouterLink } from '@angular/router';
import { ApiService } from '../../core/services/api.service';
import { environment } from '../../../environments/environment';
import { ProductCardComponent } from '../../shared/components/product-card/product-card.component';
import { ProductCardSkeletonComponent } from '../../shared/components/product-card-skeleton/product-card-skeleton.component';
import { CarouselModule } from 'primeng/carousel';

@Component({
  selector: 'app-home',
  standalone: true,
  imports: [
    CommonModule,
    RouterLink,
    ProductCardComponent,
    ProductCardSkeletonComponent,
    CarouselModule,
  ],
  templateUrl: './home.component.html',
})
export class HomeComponent implements OnInit {
  private apiService = inject(ApiService);

  groupedProducts: { [key: string]: any[] } = {};
  categories: string[] = [];
  isLoading = true;
  error: string | null = null;

  responsiveOptions: any[] = [];

  private imageBaseUrl = environment.imageBaseUrl;

  ngOnInit(): void {
    this.loadRelevantProducts();
    this.responsiveOptions = [
      {
        breakpoint: '992px',
        numVisible: 1,
        numScroll: 1,
      },
      {
        breakpoint: '768px',
        numVisible: 1,
        numScroll: 1,
      },
    ];
  }

  loadRelevantProducts(): void {
    this.isLoading = true;
    this.error = null;
    this.apiService.products
      .getAll()
      .then((response) => {
        const rawData = response.data;
        this.categories = Object.keys(rawData);

        for (const category in rawData) {
          const products = rawData[category];

          this.groupedProducts[category] = products.map((product: any) => ({
            ...product,
            name: product.nome,
            price: product.preco,
            image_url:
              product.imagens?.length > 0
                ? `${this.imageBaseUrl}/${product.imagens[0].image_url}`.replace(
                    /\\/g,
                    '/'
                  )
                : `https://placehold.co/400x400/1E1E1E/BEF264?text=Sem+Imagem`,
          }));
        }

        this.isLoading = false;
      })
      .catch((err) => {
        console.error('Erro ao buscar produtos relevantes:', err);
        this.error =
          'Não foi possível carregar os produtos. Tente novamente mais tarde.';
        this.isLoading = false;
      });
  }
}
