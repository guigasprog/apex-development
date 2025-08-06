import { Component, inject } from '@angular/core';
import { CommonModule } from '@angular/common';
import {
  FormBuilder,
  FormGroup,
  ReactiveFormsModule,
  Validators,
} from '@angular/forms';
import { Router, RouterLink, ActivatedRoute } from '@angular/router';
import { ApiService } from '../../../core/services/api.service';
import { login } from '../../../core/state/auth.store';
import { MessageService } from 'primeng/api';
import { ToastModule } from 'primeng/toast';
import { DialogModule } from 'primeng/dialog';
import { ButtonModule } from 'primeng/button';
import { InputTextModule } from 'primeng/inputtext';

@Component({
  selector: 'app-login',
  standalone: true,
  imports: [
    CommonModule,
    ReactiveFormsModule,
    RouterLink,
    ToastModule,
    DialogModule,
    ButtonModule,
    InputTextModule,
  ],
  providers: [MessageService],
  templateUrl: './login.component.html',
})
export class LoginComponent {
  private fb = inject(FormBuilder);
  private apiService = inject(ApiService);
  private router = inject(Router);
  private route = inject(ActivatedRoute);
  private messageService = inject(MessageService);

  loginForm: FormGroup;
  resetForm: FormGroup;

  displayForgotPasswordDialog = false;
  resetStep = 1; // 1: email, 2: code, 3: new password
  isLoading = false;

  constructor() {
    this.loginForm = this.fb.group({
      email: ['', [Validators.required, Validators.email]],
      password: ['', [Validators.required]],
    });

    this.resetForm = this.fb.group(
      {
        email: ['', [Validators.required, Validators.email]],
        code: [
          '',
          [
            Validators.required,
            Validators.minLength(6),
            Validators.maxLength(6),
          ],
        ],
        password: ['', [Validators.required, Validators.minLength(6)]],
        confirmPassword: ['', Validators.required],
      },
      { validators: this.passwordMatchValidator }
    );
  }

  passwordMatchValidator(form: FormGroup) {
    return form.get('password')?.value === form.get('confirmPassword')?.value
      ? null
      : { mismatch: true };
  }

  // --- Lógica do Diálogo de Recuperação de Senha ---

  showForgotPasswordDialog() {
    this.resetStep = 1;
    this.resetForm.reset();
    // Preenche o e-mail no formulário de reset se já estiver no formulário de login
    this.resetForm.get('email')?.setValue(this.loginForm.get('email')?.value);
    this.displayForgotPasswordDialog = true;
  }

  requestResetCode() {
    if (this.resetForm.get('email')?.invalid) {
      this.messageService.add({
        severity: 'warn',
        summary: 'Atenção',
        detail: 'Por favor, insira um e-mail válido.',
      });
      return;
    }
    this.isLoading = true;
    this.apiService.auth
      .forgotPassword(this.resetForm.get('email')?.value)
      .then(() => {
        this.messageService.add({
          severity: 'success',
          summary: 'Sucesso',
          detail: 'Código enviado para seu e-mail!',
        });
        this.resetStep = 2;
      })
      .catch((err) =>
        this.messageService.add({
          severity: 'error',
          summary: 'Erro',
          detail: err.response?.data?.error || 'E-mail não encontrado.',
        })
      )
      .finally(() => (this.isLoading = false));
  }

  submitVerificationCode() {
    if (this.resetForm.get('code')?.invalid) {
      this.messageService.add({
        severity: 'warn',
        summary: 'Atenção',
        detail: 'O código deve ter 6 dígitos.',
      });
      return;
    }
    this.isLoading = true;
    this.apiService.auth
      .verifyCode(
        this.resetForm.get('email')?.value,
        this.resetForm.get('code')?.value
      )
      .then(() => {
        this.messageService.add({
          severity: 'success',
          summary: 'Sucesso',
          detail: 'Código verificado!',
        });
        this.resetStep = 3;
      })
      .catch((err) =>
        this.messageService.add({
          severity: 'error',
          summary: 'Erro',
          detail: err.response?.data?.error || 'Código inválido ou expirado.',
        })
      )
      .finally(() => (this.isLoading = false));
  }

  submitNewPassword() {
    if (
      this.resetForm.get('password')?.invalid ||
      this.resetForm.get('confirmPassword')?.invalid
    ) {
      this.messageService.add({
        severity: 'warn',
        summary: 'Atenção',
        detail: 'A senha precisa ter no mínimo 6 caracteres.',
      });
      return;
    }
    if (this.resetForm.hasError('mismatch')) {
      this.messageService.add({
        severity: 'warn',
        summary: 'Atenção',
        detail: 'As senhas não coincidem.',
      });
      return;
    }
    this.isLoading = true;
    const { email, code, password } = this.resetForm.value;
    this.apiService.auth
      .resetPassword({ email, code, password })
      .then(() => {
        this.displayForgotPasswordDialog = false;
        this.messageService.add({
          severity: 'success',
          summary: 'Sucesso',
          detail: 'Senha alterada! Você já pode fazer login.',
        });
      })
      .catch((err) =>
        this.messageService.add({
          severity: 'error',
          summary: 'Erro',
          detail:
            err.response?.data?.error || 'Não foi possível redefinir a senha.',
        })
      )
      .finally(() => (this.isLoading = false));
  }

  onSubmit() {
    if (this.loginForm.invalid) {
      return;
    }

    this.apiService.auth
      .login(this.loginForm.value)
      .then((response) => {
        login(response.data.cliente, response.data.token);
        const redirectUrl =
          this.route.snapshot.queryParams['redirectUrl'] || '/';
        this.router.navigateByUrl(redirectUrl);
      })
      .catch((error) => {
        console.error('Falha no login:', error.response?.data || error.message);
        this.messageService.add({
          severity: 'error',
          summary: 'Falha no Login',
          detail: 'Credenciais inválidas. Tente novamente.',
        });
      });
  }
}
