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
                LIMIT 10`; 
            
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
                    (LOWER(p.nome) LIKE LOWER(?) OR LOWER(p.descricao) LIKE LOWER(?)) -- Busca no nome OU descrição
                ORDER BY 
                    p.nome ASC`;
            tenantDb.all(query, [`%${queryTerm}%`, `%${queryTerm}%`], (err, rows) => {
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

// --- ADICIONADO: Rota POST /api/products/interactions ---
router.post('/interactions', async (req, res, next) => {
  if (!req.tenant || !req.tenant.id) {
    return res.status(401).json({ error: 'Loja não identificada.' });
  }

  const { tipo, texto_busca, produto_id } = req.body;

  if (!tipo || (tipo !== 'search' && tipo !== 'view')) {
    return res.status(400).json({ error: 'Tipo de interação inválido. Deve ser "view" ou "search".' });
  }
  if (tipo === 'search' && (!texto_busca || texto_busca.trim() === '')) {
    return res.status(400).json({ error: 'Texto de busca é obrigatório para o tipo "search".' });
  }
  if (tipo === 'view' && !produto_id) {
    return res.status(400).json({ error: 'Produto ID é obrigatório para o tipo "view".' });
  }

  let tenantDb = null;
  try {
    tenantDb = await connectTenantDb(req.tenant.id);

    await new Promise((resolve, reject) => {
      const query = `
        INSERT INTO produto_interacoes (produto_id, tipo, texto_busca) 
        VALUES (?, ?, ?)
      `;
      
      const params = [
        tipo === 'view' ? produto_id : null, // produto_id é nulo se for 'search'
        tipo,
        tipo === 'search' ? texto_busca : null // texto_busca é nulo se for 'view'
      ];

      // Use .run() para queries de INSERT/UPDATE/DELETE
      tenantDb.run(query, params, function(err) { 
        if (err) {
          return reject(err);
        }
        resolve({ id: this.lastID }); // Retorna o ID da linha inserida
      });
    });

    res.status(201).json({ message: 'Interação registrada com sucesso.' });

  } catch (error) {
    console.error("Erro ao registrar interação:", error);
    next(error);
  } finally {
    closeDbConnection(tenantDb, `tenant_${req.tenant?.id}.db`);
  }
});

router.get('/:id', async (req, res, next) => {
  if (!req.tenant || !req.tenant.id) {
    return res.status(401).json({ error: 'Loja não identificada.' });
  }
  
  const { id } = req.params;
  let tenantDb = null;

  try {
    tenantDb = await connectTenantDb(req.tenant.id);
    
    // 1. Busca os dados principais E as especificações do produto
    const product = await new Promise((resolve, reject) => {
      // CORREÇÃO: Adicionadas as colunas de especificação
      const query = `
        SELECT id, nome as name, descricao as description, preco as price,
               peso_kg, comprimento_cm, largura_cm, altura_cm 
        FROM produtos 
        WHERE id = ?`;
      
      tenantDb.get(query, [id], (err, row) => {
        if (err) return reject(err);
        resolve(row);
      });
    });

    if (!product) {
      return res.status(404).json({ error: 'Produto não encontrado.' });
    }

    // 2. Busca as imagens do produto (Lógica mantida)
    const images = await new Promise((resolve, reject) => {
      const query = `SELECT image_url, descricao FROM imagens_produto WHERE produto_id = ? ORDER BY id ASC`;
      tenantDb.all(query, [id], (err, rows) => {
        if (err) return reject(err);
        resolve(rows || []);
      });
    });

    // 3. CORREÇÃO: Removemos a query à tabela 'produto_especificacoes'
    // Em vez disso, formatamos os dados que já buscamos
    
    const specifications = [];
    if (product.peso_kg) {
      specifications.push({ nome: 'Peso', valor: `${product.peso_kg} kg` });
    }
    if (product.comprimento_cm) {
      specifications.push({ nome: 'Comprimento', valor: `${product.comprimento_cm} cm` });
    }
    if (product.largura_cm) {
      specifications.push({ nome: 'Largura', valor: `${product.largura_cm} cm` });
    }
    if (product.altura_cm) {
      specifications.push({ nome: 'Altura', valor: `${product.altura_cm} cm` });
    }
    
    // 4. Limpa as colunas originais do objeto produto para não duplicar dados
    delete product.peso_kg;
    delete product.comprimento_cm;
    delete product.largura_cm;
    delete product.altura_cm;

    // 5. Combina tudo e envia
    res.json({
      ...product,
      images: images,
      specifications: specifications // Envia o array formatado
    });

  } catch (error) {
    console.error(`Erro ao buscar produto ${id}:`, error);
    next(error);
  } finally {
    closeDbConnection(tenantDb, `tenant_${req.tenant?.id}.db`);
  }
});

export default router;