import { Component, OnInit, inject } from '@angular/core';
import { CommonModule, CurrencyPipe } from '@angular/common';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { DomSanitizer, SafeHtml } from '@angular/platform-browser';
import { DialogModule } from 'primeng/dialog';
import { ButtonModule } from 'primeng/button';
import { InputTextModule } from 'primeng/inputtext';
import { InputNumberModule } from 'primeng/inputnumber';
import { ProgressSpinnerModule } from 'primeng/progressspinner';
import { ToastModule } from 'primeng/toast';
import { MessageService } from 'primeng/api';
import { ApiService } from '../../../core/services/api.service';
import { environment } from '../../../../environments/environment';
import { FormsModule } from '@angular/forms';

@Component({
  selector: 'app-product-detail',
  standalone: true,
  imports: [
    CommonModule,
    RouterLink,
    FormsModule,
    DialogModule,
    ButtonModule,
    InputTextModule,
    InputNumberModule,
    ProgressSpinnerModule,
    ToastModule,
    CurrencyPipe,
  ],
  providers: [MessageService],
  templateUrl: './product-detail.component.html',
  styleUrls: ['./product-detail.component.scss'],
})
export class ProductDetailComponent implements OnInit {
  private route = inject(ActivatedRoute);
  private apiService = inject(ApiService);
  private sanitizer = inject(DomSanitizer);
  private messageService = inject(MessageService);

  product: any = null;
  isLoading = true;
  error: string | null = null;
  selectedImageUrl: string = '';
  relatedProducts: any[] = [];

  descricaoContent: SafeHtml | undefined;
  sobreContent: SafeHtml | undefined;

  displayAddToCartDialog = false;
  quantity = 1;
  cep = '';
  isCalculatingShipping = false;
  shippingInfo: any = null;
  totalCost: number = 0;

  private imageBaseUrl = environment.imageBaseUrl;

  ngOnInit(): void {
    this.route.paramMap.subscribe((params) => {
      const productId = params.get('id');
      if (productId) {
        this.loadProductDetails(productId);
      } else {
        this.isLoading = false;
        this.error = 'Nenhum ID de produto foi fornecido.';
      }
    });
  }

  private stripInlineStyles(html: string): string {
    if (!html) return '';
    return html.replace(/ style="[^"]*"/g, '');
  }

  loadProductDetails(id: string): void {
    window.scrollTo(0, 0);

    this.isLoading = true;
    this.error = null;
    this.product = null;
    this.relatedProducts = [];

    this.apiService.products
      .getById(id)
      .then((response) => {
        const productData = response.data;

        if (productData.imagens && productData.imagens.length > 0) {
          productData.imagens = productData.imagens.map((img: any) => ({
            ...img,
            full_url: `${this.imageBaseUrl}/${img.image_url}`.replace(
              /\\/g,
              '/'
            ),
          }));
          this.selectedImageUrl = productData.imagens[0].full_url;
        } else {
          this.selectedImageUrl = `https://placehold.co/600x600/1E1E1E/BEF264?text=${encodeURIComponent(
            productData.nome
          )}`;
        }

        this.product = productData;

        if (this.product.descricao) {
          this.descricaoContent = this.sanitizer.bypassSecurityTrustHtml(
            this.stripInlineStyles(this.product.descricao)
          );
        }
        if (this.product.sobre_o_item) {
          this.sobreContent = this.sanitizer.bypassSecurityTrustHtml(
            this.stripInlineStyles(this.product.sobre_o_item)
          );
        }

        // Após carregar o produto principal, busca os relacionados
        if (this.product.categoria?.idCategoria) {
          this.loadRelatedProducts(
            this.product.categoria.idCategoria,
            this.product.id
          );
        }

        this.isLoading = false;
      })
      .catch((err) => {
        console.error('Erro ao buscar detalhes do produto:', err);
        this.error =
          'Não foi possível carregar o produto. Tente novamente mais tarde.';
        this.isLoading = false;
      });
  }

  loadRelatedProducts(categoryId: number, currentProductId: number): void {
    this.apiService.products
      .getAll()
      .then((response) => {
        this.relatedProducts = response.data
          .filter(
            (p: any) =>
              p.categoria?.idCategoria === categoryId &&
              p.id !== currentProductId
          )
          .slice(0, 3)
          .map((p: any) => ({
            id: p.id,
            name: p.nome,
            price: p.preco,
            image_url:
              p.imagens && p.imagens.length > 0
                ? `${this.imageBaseUrl}/${p.imagens[0].image_url}`.replace(
                    /\\/g,
                    '/'
                  )
                : `https://placehold.co/400x400/1E1E1E/BEF264?text=${encodeURIComponent(
                    p.nome
                  )}`,
          }));
      })
      .catch((err) => {
        console.error('Erro ao buscar produtos relacionados:', err);
      });
  }

  selectImage(imageUrl: string): void {
    this.selectedImageUrl = imageUrl;
  }

  showAddToCartDialog(): void {
    this.quantity = 1;
    this.cep = '';
    this.shippingInfo = null;
    this.totalCost = 0;
    this.displayAddToCartDialog = true;
  }

  resetShipping(): void {
    this.shippingInfo = null;
    this.totalCost = 0;
  }

  calculateShipping(): void {
    if (!this.cep || this.cep.replace(/\D/g, '').length !== 8) {
      this.messageService.add({
        severity: 'warn',
        summary: 'Atenção',
        detail: 'Por favor, insira um CEP válido.',
      });
      return;
    }
    this.isCalculatingShipping = true;
    this.resetShipping();

    const payload = {
      to_postal_code: this.cep.replace(/\D/g, ''),
      products: [{ id: this.product.id, quantity: this.quantity }],
    };

    this.apiService.shipping
      .calculate(payload)
      .then((response) => {
        const sedexOption =
          response.data && response.data.length > 0 ? response.data[0] : null;
        if (sedexOption) {
          this.shippingInfo = {
            ...sedexOption,
            price: parseFloat(sedexOption.price),
          };
          this.updateTotal();
        } else {
          this.messageService.add({
            severity: 'error',
            summary: 'Erro',
            detail: 'SEDEX não disponível para este CEP.',
          });
        }
      })
      .catch(() => {
        this.messageService.add({
          severity: 'error',
          summary: 'Erro',
          detail: 'Não foi possível calcular o frete.',
        });
      })
      .finally(() => {
        this.isCalculatingShipping = false;
      });
  }

  updateTotal(): void {
    if (this.product && this.shippingInfo) {
      const subtotal = this.product.preco * this.quantity;
      this.totalCost = subtotal + this.shippingInfo.price;
    }
  }

  confirmAddToCart(): void {
    console.log('Adicionando ao carrinho:', {
      productId: this.product.id,
      name: this.product.nome,
      quantity: this.quantity,
      unitPrice: this.product.preco,
      shipping: this.shippingInfo,
    });

    this.displayAddToCartDialog = false;
    this.messageService.add({
      severity: 'success',
      summary: 'Sucesso',
      detail: `${this.quantity}x ${this.product.nome} adicionado(s) ao carrinho!`,
    });
  }
}
