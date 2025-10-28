import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ProductListComponent } from '../product-list/product-list.component';
import { Product } from '../../models/product.model';
import { ProductService } from '../../services/product.service';

@Component({
  selector: 'app-storefront',
  standalone: true,
  imports: [CommonModule, ProductListComponent],
  templateUrl: './storefront.component.html',
  styleUrls: ['./storefront.component.scss']
})
export class StorefrontComponent implements OnInit { // Implementa OnInit

  products: Product[] = []; // Array para guardar os produtos
  isLoadingProducts = true; // Flag para mostrar loading
  productLoadError = ''; // Mensagem de erro

  // Injeta o ProductService
  constructor(private productService: ProductService) { }

  ngOnInit(): void {
    this.loadProducts(); // Chama o método para buscar produtos ao iniciar
  }

  loadProducts(): void {
    this.isLoadingProducts = true;
    this.productLoadError = '';
    this.productService.getProducts().subscribe({
      next: (data) => {
        this.products = data;
        this.isLoadingProducts = false;
        console.log('Produtos carregados:', this.products);
      },
      error: (err) => {
        console.error('Erro ao carregar produtos:', err);
        this.productLoadError = 'Não foi possível carregar os produtos no momento.';
        this.isLoadingProducts = false;
      }
    });
  }
}
