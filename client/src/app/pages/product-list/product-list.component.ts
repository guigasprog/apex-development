// Em src/app/components/product-list/product-list.component.ts (ou sua home page)

import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common'; // Para *ngIf, *ngFor, async
import { ProductService } from '../../services/product.service';
import { ProductGroup } from '../../models/product.model'; // Importa o novo model
import { ProductCardComponent } from '../../components/product-card/product-card.component'; // Importa o card
import { Observable, of } from 'rxjs';
import { catchError, tap } from 'rxjs/operators';
import { ProductGridComponent } from '../../components/product-grid/product-grid.component';

@Component({
  selector: 'app-product-list',
  standalone: true,
  imports: [CommonModule, ProductGridComponent], // Certifique-se de importar o ProductCard
  templateUrl: './product-list.component.html',
  styleUrls: ['./product-list.component.scss']
})
export class ProductListComponent implements OnInit {

  // A propriedade agora é um Observable de grupos de produtos
  productGroups$!: Observable<ProductGroup[]>;
  error: string | null = null;

  isLoading: boolean = true;

  constructor(private productService: ProductService) { }

  ngOnInit(): void {
    this.productGroups$ = this.productService.getProducts().pipe(
      tap(() => {
        console.log(this.isLoading)
        this.isLoading = false;
        console.log(this.isLoading)
      }),
      catchError(err => {
        this.isLoading = false
        console.error('Erro ao buscar grupos de produtos:', err);
        this.error = 'Não foi possível carregar os produtos. Tente novamente mais tarde.';
        return of([]); // Retorna um array vazio para o async pipe
      })
    )
  }
}
