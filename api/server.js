import express from 'express';
import cors from 'cors';
import dotenv from 'dotenv';
import path from 'path';
import { fileURLToPath } from 'url';
import { identifyTenant } from './src/middleware/tenant.js';
import tenantRoutes from './src/routes/tenant.js';
import productRoutes from './src/routes/product.js';

dotenv.config();

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

const app = express();
const PORT = process.env.PORT || 3000;

const corsOptions = {
  origin: function (origin, callback) {
    if (!origin) {
        return callback(null, true);
    }

    try {
        const hostname = new URL(origin).hostname;

        if (hostname.endsWith('.vibevault.com')) {
            callback(null, true);
        } else {
            callback(new Error('Acesso negado pela política de CORS')); // Barra a entrada
        }
    } catch (e) {
        callback(new Error('Origem inválida'));
    }
  }
};
app.use(cors(corsOptions));

// --- Middlewares ---
app.use(express.json());
const staticFilesPath = path.join(__dirname, '../apex-control/files'); 
console.log(`Serving static files from: ${staticFilesPath}`);
app.use(express.static(staticFilesPath));
app.use(identifyTenant);

app.use('/api/tenant', tenantRoutes);
app.use('/api/products', productRoutes);

// Rota de teste
app.get('/', (req, res) => {
    res.send('API da Apex Store está no ar!');
});

app.use((err, req, res, next) => {
  console.error('ERRO NÃO TRATADO:', err.stack || err.message);
  res.status(500).json({ error: 'Ocorreu um erro interno no servidor.' });
});

// --- Inicia o Servidor ---
app.listen(PORT, () => {
    console.log(`🚀 Servidor Express rodando na porta ${PORT}`);
});