import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import {
  FormBuilder,
  FormGroup,
  ReactiveFormsModule,
  Validators,
} from '@angular/forms';
import { Router, RouterLink } from '@angular/router';
import { ApiService } from '../../../core/services/api.service';
import { MessageService } from 'primeng/api';
import { ToastModule } from 'primeng/toast';
import { ProgressSpinnerModule } from 'primeng/progressspinner';
import { login } from '../../../core/state/auth.store';

@Component({
  selector: 'app-register',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    RouterLink,
    ToastModule,
    ProgressSpinnerModule,
  ],
  providers: [MessageService],
  templateUrl: './register.component.html',
})
export class RegisterComponent {
  private fb = inject(FormBuilder);
  private apiService = inject(ApiService);
  private router = inject(Router);
  private messageService = inject(MessageService);

  registerForm: FormGroup;
  isSearchingCep = false;

  constructor() {
    this.registerForm = this.fb.group({
      nome: ['', Validators.required],
      email: ['', [Validators.required, Validators.email]],
      cpf: ['', Validators.required],
      telefone: [''],
      password: ['', [Validators.required, Validators.minLength(6)]],
      cep: ['', Validators.required],
      logradouro: ['', Validators.required],
      numero: ['', Validators.required],
      bairro: ['', Validators.required],
      cidade: ['', Validators.required],
      estado: ['', Validators.required],
      complemento: [''],
    });
  }

  onCepBlur() {
    const cepControl = this.registerForm.get('cep');
    if (cepControl && cepControl.value) {
      const cep = cepControl.value.replace(/\D/g, '');
      if (cep.length === 8) {
        this.isSearchingCep = true;
        this.apiService.viacep
          .get(cep)
          .then((response) => {
            if (response.data.erro) {
              this.messageService.add({
                severity: 'warn',
                summary: 'Atenção',
                detail: 'CEP não encontrado.',
              });
            } else {
              this.registerForm.patchValue({
                logradouro: response.data.logradouro,
                bairro: response.data.bairro,
                cidade: response.data.localidade,
                estado: response.data.uf,
                complemento: response.data.complemento,
              });
            }
          })
          .catch((error) => {
            console.error('Erro ao buscar CEP:', error);
            this.messageService.add({
              severity: 'error',
              summary: 'Erro de Rede',
              detail: 'Não foi possível consultar o CEP.',
            });
          })
          .finally(() => {
            this.isSearchingCep = false;
          });
      }
    }
  }

  onSubmit() {
    if (this.registerForm.invalid) {
      this.messageService.add({
        severity: 'warn',
        summary: 'Atenção',
        detail: 'Por favor, preencha todos os campos obrigatórios.',
      });
      return;
    }

    this.apiService.auth
      .register(this.registerForm.value)
      .then((response) => {
        login(response.data.cliente, response.data.token);
        this.router.navigate(['/']);
      })
      .catch((error) => {
        console.error(
          'Falha no registro:',
          error.response?.data || error.message
        );
        this.messageService.add({
          severity: 'error',
          summary: 'Erro no Registro',
          detail:
            error.response?.data?.error || 'Não foi possível criar a conta.',
        });
      });
  }
}
