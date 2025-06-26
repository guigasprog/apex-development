import { Component, inject, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ApiService } from '../../core/services/api.service';
import { environment } from '../../../environments/environment';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-home',
  standalone: true,
  imports: [CommonModule, RouterLink],
  templateUrl: './home.component.html',
})
export class HomeComponent implements OnInit {
  private apiService = inject(ApiService);

  products: any[] = [];
  isLoading = true;
  error: string | null = null;

  productsApiBaseUrl = environment.imageBaseUrl;

  constructor() {}

  ngOnInit(): void {
    this.loadProducts();
  }

  loadProducts(): void {
    this.isLoading = true;
    this.error = null;

    this.apiService.products
      .getAll()
      .then((response) => {
        this.products = response.data.map((product: any) => {
          const imageUrl =
            product.imagens && product.imagens.length > 0
              ? `${this.productsApiBaseUrl}${product.imagens[0].image_url}`.replace(
                  /\\/g,
                  '/'
                )
              : 'https://placehold.co/400x400/1E1E1E/BEF264?text=Sem+Imagem';

          return {
            id: product.id,
            name: product.nome,
            price: product.preco,
            image_url: imageUrl,
          };
        });
        this.isLoading = false;
      })
      .catch((err) => {
        console.error('Erro ao buscar produtos:', err);
        this.error =
          'Não foi possível carregar os produtos. Verifique se o servidor está rodando e tente novamente.';
        this.isLoading = false;
      });
  }
}
