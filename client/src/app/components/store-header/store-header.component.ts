import { Component, OnInit } from '@angular/core';
import { CommonModule } from '@angular/common';
import { FormsModule } from '@angular/forms'; // Necessário para [(ngModel)]
import { Router, RouterModule } from '@angular/router'; // Necessário para routerLink e navegação
import { Observable, BehaviorSubject, of, Subject } from 'rxjs'; // RxJS para reatividade
import { debounceTime, distinctUntilChanged, switchMap, catchError, tap } from 'rxjs/operators'; // Operadores RxJS
import { TenantService, TenantTheme } from '../../services/tenant.service'; // Serviço de tema
import { ProductService } from '../../services/product.service'; // Serviço de produto
import { environment } from '../../../environments/environment'; // Para URL base das imagens

// Interface para os resultados do autocomplete
interface AutocompleteResult {
  id: number;
  name: string;
}

@Component({
  selector: 'app-store-header', // Seletor do componente
  standalone: true, // Define como componente standalone
  imports: [ // Módulos e componentes necessários
    CommonModule,    // Para diretivas *ngIf, *ngFor, async pipe
    FormsModule,     // Para [(ngModel)] na barra de busca
    RouterModule     // Para routerLink no logo
  ],
  templateUrl: './store-header.component.html', // Arquivo HTML do componente
  styleUrls: ['./store-header.component.scss'] // Arquivo SCSS (opcional se usar global)
})
export class StoreHeaderComponent implements OnInit {
  // Observables para dados dinâmicos
  theme$: Observable<TenantTheme | null>;
  isLoggedIn$: Observable<boolean>;
  cartItemCount$: Observable<number>;
  autocompleteResults: AutocompleteResult[] = [];

  // Propriedades para controle
  apiImage: string; // URL base para imagens (logo)
  searchTerm: string = '';
  showAutocomplete: boolean = false;

  // Gerenciamento de estado interno (simulado)
  private loggedInSubject = new BehaviorSubject<boolean>(false);
  private cartCountSubject = new BehaviorSubject<number>(0);
  private searchSubject = new Subject<string>(); // Para debounce da busca

  constructor(
    private tenantService: TenantService,
    private router: Router,
    private productService: ProductService // Injeta o serviço de produtos
  ) {
    this.theme$ = this.tenantService.tenantTheme$;
    this.isLoggedIn$ = this.loggedInSubject.asObservable();
    this.cartItemCount$ = this.cartCountSubject.asObservable();
    this.apiImage = environment.imageDB; // Pega a URL base das imagens do environment
  }

  ngOnInit(): void {

  }

  // --- Métodos de Autenticação (Simulados) ---
  login(): void {
      this.loggedInSubject.next(true);
      this.cartCountSubject.next(3); // Adiciona itens ao carrinho (exemplo)
      console.log('Simulando Login');
  }
  register(): void { this.router.navigate(['/register']); } // Exemplo de navegação
  logout(): void {
      this.loggedInSubject.next(false);
      this.cartCountSubject.next(0);
      console.log('Simulando Logout');
  }
  goToProfile(): void { this.router.navigate(['/profile']); } // Exemplo

  // --- Métodos do Carrinho ---
  goToCart(): void { this.router.navigate(['/cart']); } // Exemplo

  // --- Métodos da Busca ---
  onSearchSubmit(): void {
    this.showAutocomplete = false; // Esconde autocomplete ao submeter
    if (this.searchTerm.trim()) {
      this.router.navigate(['/search'], { queryParams: { q: this.searchTerm } });
    }
  }

  onSearchInput(): void {
    this.productService.getAutocomplete(this.searchTerm).subscribe(
      (success) => {
        this.autocompleteResults = success;
        this.showAutocomplete = this.autocompleteResults.length > 0;
      }
    )
  }

  selectResult(result: AutocompleteResult): void {
    this.searchTerm = result.name; // Preenche a busca com o nome
    this.showAutocomplete = false;
    // Navega para a página do produto específico
    this.router.navigate(['/product', result.id]); // Ajuste a rota se necessário
  }

  // Fecha o dropdown do autocomplete ao clicar fora do input
   closeAutocomplete(): void {
     // Pequeno delay para permitir que o clique no item seja registrado antes de esconder
     setTimeout(() => this.showAutocomplete = false, 150);
   }
}
