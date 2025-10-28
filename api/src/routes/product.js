// src/routes/product.js
import { Router } from 'express';
import { connectTenantDb, closeDbConnection } from '../database/connection.js';

const router = Router();

// Rota: GET /api/products/
router.get('/', async (req, res, next) => {
    // Verifica se o middleware identificou o tenant
    if (!req.tenant || !req.tenant.id) {
        return res.status(404).json({ error: 'Loja não identificada. Acesso via subdomínio necessário.' });
    }

    let tenantDb = null; // Guarda a conexão específica do tenant
    try {
        tenantDb = await connectTenantDb(req.tenant.id); // Conecta ao banco correto!

        const products = await new Promise((resolve, reject) => {
            const query = `
                SELECT
                    p.id, p.nome as name, p.descricao as description, p.preco as price,
                    (SELECT pi.image_url FROM imagens_produto pi WHERE pi.produto_id = p.id ORDER BY pi.id LIMIT 1) as main_image_url
                FROM
                    produtos p
                ORDER BY
                    p.nome ASC`; // tenant_id não é mais necessário na query, pois já estamos no DB certo
            tenantDb.all(query, [], (err, rows) => { // Não precisa mais do tenant_id aqui
                if (err) reject(err);
                resolve(rows);
            });
        });
        res.json(products || []);

    } catch (error) {
        console.error(`Erro ao buscar produtos para tenant ${req.tenant.id}:`, error);
        // Passa o erro para o error handler do Express
        next(error); // É melhor usar next(error) do que res.status(500) aqui
    } finally {
        // Garante que a conexão com o banco do tenant seja fechada
        closeDbConnection(tenantDb, `tenant_${req.tenant?.id}.db`);
    }
});

// Rota: GET /api/products/:productId/images (Mesma lógica de conexão)
router.get('/:productId/images', async (req, res, next) => {
    if (!req.tenant || !req.tenant.id) {
        return res.status(404).json({ error: 'Loja não identificada.' });
    }
    
    const productId = req.params.productId;
    let tenantDb = null;
    
    try {
        tenantDb = await connectTenantDb(req.tenant.id);
        const images = await new Promise((resolve, reject) => {
            const query = `SELECT image_url, descricao FROM imagens_produto WHERE produto_id = ? ORDER BY id ASC`;
            tenantDb.all(query, [productId], (err, rows) => {
                if (err) reject(err);
                resolve(rows);
            });
        });
        res.json(images || []);
    } catch (error) {
         console.error(`Erro ao buscar imagens para tenant ${req.tenant.id}, produto ${productId}:`, error);
        next(error);
    } finally {
        closeDbConnection(tenantDb, `tenant_${req.tenant?.id}.db`);
    }
});

// --- ADICIONADO: Rota GET /api/products/autocomplete ---
router.get('/autocomplete', async (req, res, next) => {
    if (!req.tenant || !req.tenant.id) {
        return res.status(401).json({ error: 'Loja não identificada.' });
    }

    const searchTerm = req.query.term;
    if (!searchTerm || searchTerm.length < 2) {
        return res.json([]);
    }

    let tenantDb = null;
    try {
        tenantDb = await connectTenantDb(req.tenant.id); // Connection is opened correctly

        const results = await new Promise((resolve, reject) => {
            const query = `
                SELECT id, nome as name
                FROM produtos
                WHERE LOWER(nome) LIKE LOWER(?)
                LIMIT 10`; // tenant_id is not needed as we are in the correct DB
            
            // CORRECTION: Use 'tenantDb' here, not 'db'
            tenantDb.all(query, [`%${searchTerm}%`], (err, rows) => {
                if (err) reject(err);
                resolve(rows);
            });
        });
        res.json(results || []);
    } catch (error) {
        console.error("Erro no autocomplete:", error);
        next(error);
    } finally {
        closeDbConnection(tenantDb, `tenant_${req.tenant?.id}.db`);
    }
});

// --- ADICIONADO: Rota GET /api/products/search ---
router.get('/search', async (req, res, next) => {
    if (!req.tenant || !req.tenant.id) {
        return res.status(401).json({ error: 'Loja não identificada.' });
    }
    
    const queryTerm = req.query.q; // Pega o termo da query string ?q=...
    if (!queryTerm) {
        return res.status(400).json({ error: 'Termo de busca não fornecido.' });
    }

    let tenantDb = null;
    try {
        tenantDb = await connectTenantDb(req.tenant.id);
        const products = await new Promise((resolve, reject) => {
            const query = `
                SELECT 
                    p.id, p.nome as name, p.descricao as description, p.preco as price,
                    (SELECT pi.image_url FROM imagens_produto pi WHERE pi.produto_id = p.id ORDER BY pi.id LIMIT 1) as main_image_url
                FROM 
                    produtos p 
                WHERE 
                    p.tenant_id = ? AND 
                    (LOWER(p.nome) LIKE LOWER(?) OR LOWER(p.descricao) LIKE LOWER(?)) -- Busca no nome OU descrição
                ORDER BY 
                    p.nome ASC`;
            db.all(query, [req.tenant.id, `%${queryTerm}%`, `%${queryTerm}%`], (err, rows) => {
                if (err) reject(err);
                resolve(rows);
            });
        });
        res.json(products || []);
    } catch (error) {
        console.error("Erro na busca:", error);
        next(error);
    } finally {
        closeDbConnection(tenantDb, `tenant_${req.tenant?.id}.db`);
    }
});

export default router;