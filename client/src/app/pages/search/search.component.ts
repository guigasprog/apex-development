import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common'; // Necessário para *ngIf, async pipe
import { ActivatedRoute, Router } from '@angular/router'; // Para ler parâmetros da URL
import { ProductService } from '../../services/product.service'; // Serviço de produtos
import { Product } from '../../models/product.model'; // Modelo de dados do produto
import { ProductListComponent } from '../../components/product-list/product-list.component'; // Reutiliza o componente de lista
import { catchError, map, switchMap, tap } from 'rxjs/operators'; // Operadores RxJS
import { Observable, of } from 'rxjs'; // Observable e 'of' para fallback

@Component({
  selector: 'app-search', // Seletor do componente
  standalone: true, // Define como componente standalone
  imports: [
    CommonModule,         // Para diretivas
    ProductListComponent  // Para usar <app-product-list>
  ],
  templateUrl: './search.component.html', // Arquivo HTML
  styleUrls: ['./search.component.scss']   // Arquivo CSS
})
export class SearchComponent implements OnInit { // Renomeado para SearchPageComponent

  searchTerm$: Observable<string | null>;    // Observable para o termo de busca
  searchResults$: Observable<Product[]>; // Observable para os resultados
  isLoading = true;                      // Flag de carregamento
  error: string | null = null;            // Mensagem de erro

  constructor(
    private router: Router,
    private route: ActivatedRoute,       // Para ler a URL
    private productService: ProductService // Para buscar os produtos
  ) {
    // Cria um Observable que emite o parâmetro 'q' da URL sempre que ele mudar
    this.searchTerm$ = this.route.queryParamMap.pipe(
      map(params => params.get('q')) // Extrai o valor de ?q=...
    );
    // Cria um Observable que reage às mudanças no searchTerm$
    this.searchResults$ = this.searchTerm$.pipe(
      tap(() => { // Executa antes de cada busca
        this.isLoading = true; // Ativa o loading
        this.error = null;     // Limpa erros anteriores
      }),
      switchMap(term => { // Cancela a busca anterior e inicia uma nova se o termo mudar
        if (!term) { // Se não houver termo de busca
          this.isLoading = false;
          return of([]); // Retorna um array vazio imediatamente
        }
        console.log(term)
        // Chama o serviço para buscar os produtos na API
        return this.productService.searchProducts(term).pipe(
          tap(() => this.isLoading = false), // Desativa o loading após a resposta
          catchError(err => { // Em caso de erro na chamada da API
            console.error("Erro na busca de produtos:", err);
            this.isLoading = false;
            this.error = "Não foi possível realizar a busca. Tente novamente mais tarde.";
            return of([]); // Retorna um array vazio para não quebrar o template
          })
        );
      })
    );
  }

  ngOnInit(): void {
    // A lógica principal já está configurada nos Observables no construtor
  }
}
