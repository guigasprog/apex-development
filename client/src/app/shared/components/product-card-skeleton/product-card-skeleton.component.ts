import { Component } from '@angular/core';

@Component({
  selector: 'app-product-card-skeleton',
  standalone: true,
  template: `
    <div
      class="bg-brand-surface rounded-lg overflow-hidden w-full animate-pulse"
    >
      <div class="w-full h-48 md:h-64 bg-gray-700"></div>
      <div class="p-4 space-y-3">
        <div class="h-4 bg-gray-700 rounded w-3/4"></div>
        <div class="h-6 bg-gray-700 rounded w-1/2"></div>
      </div>
    </div>
  `,
})
export class ProductCardSkeletonComponent {}
