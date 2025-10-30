// Importações necessárias (adicionado OnDestroy e Subscription)
import { Component, OnInit, OnDestroy } from '@angular/core';
import { CommonModule } from '@angular/common';
import { ActivatedRoute, Router } from '@angular/router';
import { ProductInteraction, ProductService } from '../../services/product.service';
import { Product } from '../../models/product.model';
import { catchError, map, switchMap, tap } from 'rxjs/operators';
import {  of, Subscription } from 'rxjs';
import { ProductGridComponent } from '../../components/product-grid/product-grid.component';

@Component({
  selector: 'app-search',
  standalone: true,
  imports: [
    CommonModule,
    ProductGridComponent
  ],
  templateUrl: './search.component.html',
  styleUrls: ['./search.component.scss']
})
export class SearchComponent implements OnInit, OnDestroy {

  searchTerm: string | null = null;
  searchResults: Product[] = [];
  isLoading = true;
  error: string | null = null;

  private searchSubscription?: Subscription;

  constructor(
    private router: Router,
    private route: ActivatedRoute,
    private productService: ProductService
  ) { }

  ngOnInit(): void {
    this.searchSubscription = this.route.queryParamMap.pipe(
      map(params => params.get('q')), // 1. Extrai o valor de ?q=...
      tap(term => {
        this.isLoading = true;
        this.error = null;
        this.searchTerm = term;
        this.searchResults = [];

        if (term && term.trim() !== '') {
          this.logSearchInteraction(term);
        }

      }),
      switchMap(term => { // 3. Cancela a busca anterior e inicia a nova
        if (!term) {
          return of([]); // Retorna um array vazio (como Observable)
        }
        console.log(term);
        // 4. Chama o serviço
        return this.productService.searchProducts(term).pipe(
          catchError(err => { // 5. Em caso de erro na API
            console.error("Erro na busca de produtos:", err);
            this.error = "Não foi possível realizar a busca. Tente novamente mais tarde.";
            return of([]); // Retorna um array vazio para não quebrar a stream
          })
        );
      })
    ).subscribe({ // 6. Onde a "mágica" acontece
      next: (results) => {
        // 'results' é o array de produtos retornado (seja da API ou do of([]))
        this.searchResults = results;
        this.isLoading = false; // Desativa o loading APÓS receber os dados
      },
      error: (err) => {
        // Este erro é para o caso de algo quebrar a stream (raro aqui, pois usamos catchError)
        console.error("Erro catastrófico no stream de busca:", err);
        this.isLoading = false;
        this.error = "Ocorreu um erro inesperado.";
        this.searchResults = [];
      }
    });
  }

  /**
   * Método privado para enviar o log de busca.
   * É "fire-and-forget": nós disparamos a requisição, mas não esperamos
   * ela terminar para mostrar os resultados da busca ao usuário.
   */
  private logSearchInteraction(term: string): void {
    const interactionData: ProductInteraction = {
      tipo: 'search',
      texto_busca: term,
      produto_id: null
    };

    this.productService.logInteraction(interactionData)
      .pipe(
        catchError(err => {
          console.warn('Não foi possível registrar a interação de busca:', err);
          return of(null);
        })
      )
      .subscribe();
  }

  ngOnDestroy(): void {
    // 7. Boa prática: Limpa a inscrição quando o componente é destruído
    if (this.searchSubscription) {
      this.searchSubscription.unsubscribe();
    }
  }
}
