import { Injectable } from '@angular/core';
import { BehaviorSubject, Observable, of } from 'rxjs';
import { ProductService, ProductInteraction } from './product.service'; // Importamos o serviço de produto
import { catchError } from 'rxjs/operators';

@Injectable({
  providedIn: 'root'
})
export class FavoriteService {
  private storageKey = 'userFavorites';

  // BehaviorSubject para que os componentes possam "ouvir" as mudanças em tempo real
  private favoritesSubject: BehaviorSubject<number[]>;

  constructor(private productService: ProductService) {
    // Carrega os favoritos do localStorage ao iniciar o serviço
    const existingFavorites = this.getFavoritesFromStorage();
    this.favoritesSubject = new BehaviorSubject<number[]>(existingFavorites);
  }

  // --- Métodos Privados ---

  private getFavoritesFromStorage(): number[] {
    try {
      const items = localStorage.getItem(this.storageKey);
      return items ? JSON.parse(items) : [];
    } catch (error) {
      console.error('Erro ao ler favoritos do localStorage:', error);
      return [];
    }
  }

  private saveFavoritesToStorage(favorites: number[]): void {
    try {
      localStorage.setItem(this.storageKey, JSON.stringify(favorites));
      // Notifica todos os "ouvintes" (subscribers) sobre a nova lista
      this.favoritesSubject.next(favorites);
    } catch (error) {
      console.error('Erro ao salvar favoritos no localStorage:', error);
    }
  }

  // --- Métodos Públicos ---

  /**
   * Retorna um Observable com a lista atual de IDs de produtos favoritos.
   */
  getFavorites(): Observable<number[]> {
    return this.favoritesSubject.asObservable();
  }

  /**
   * Verifica se um produto específico está favoritado (em tempo real).
   */
  isFavorite(productId: number): boolean {
    return this.favoritesSubject.value.includes(productId);
  }

  /**
   * Adiciona ou remove um produto dos favoritos.
   */
  toggleFavorite(productId: number): void {
    const currentFavorites = this.favoritesSubject.value;
    const isNowFavorite = !currentFavorites.includes(productId);

    let updatedFavorites: number[];

    if (isNowFavorite) {
      // Adiciona aos favoritos
      updatedFavorites = [...currentFavorites, productId];

      // Registra a interação de "view" (como "favoritado") no DB
      this.logFavoriteInteraction(productId);

    } else {
      // Remove dos favoritos
      updatedFavorites = currentFavorites.filter(id => id !== productId);
    }

    this.saveFavoritesToStorage(updatedFavorites);
  }

  /**
   * Envia o log para o backend (tipo 'view' com produto_id).
   */
  private logFavoriteInteraction(productId: number): void {
    const interactionData: ProductInteraction = {
      tipo: 'view', // 'view' pode ser usado para "visualização" ou "favoritado"
      produto_id: productId,
      texto_busca: null
    };

    this.productService.logInteraction(interactionData)
      .pipe(
        catchError(err => {
          console.warn('Não foi possível registrar a interação de "favoritar":', err);
          return of(null);
        })
      )
      .subscribe();
  }
}
