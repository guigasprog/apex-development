import { Component, Input } from '@angular/core';
import { CommonModule, CurrencyPipe } from '@angular/common';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-product-card',
  standalone: true,
  imports: [CommonModule, CurrencyPipe, RouterLink],
  template: `
    <a
      [routerLink]="['/product', product.id]"
      class="block bg-brand-surface rounded-lg overflow-hidden group w-64 transition-transform duration-300 hover:-translate-y-1"
    >
      <div class="relative w-full h-48 md:h-64 bg-gray-800">
        <img
          [src]="product.image_url"
          [alt]="product.name"
          class="w-full h-full object-cover group-hover:opacity-80 transition-opacity"
        />
      </div>
      <div class="p-4">
        <h3 class="font-semibold text-brand-text truncate">
          {{ product.name }}
        </h3>
        <p class="text-lg font-bold text-brand-accent mt-2">
          {{ product.price | currency : 'BRL' }}
        </p>
      </div>
    </a>
  `,
})
export class ProductCardComponent {
  @Input() product: any;
}
