import { Router } from 'express';
import { connectPermissionDb, closeDbConnection } from '../database/connection.js';

const router = Router();

// --- Helper Functions (agora aceitam a conexão do banco) ---

function getColorDetails(dbConn, name) {
    return new Promise((resolve, reject) => {
        const query = "SELECT name, label, hex_light, hex_dark FROM custom_colors WHERE name = ?";
        dbConn.get(query, [name], (err, row) => {
            if (err) reject(err);
            resolve(row || null); // Retorna null se não achar, lidaremos com o fallback depois se necessário
        });
    });
}

function getFontDetails(dbConn, name) {
    return new Promise((resolve, reject) => {
        const query = "SELECT name, label, import_url FROM custom_fonts WHERE name = ?";
        dbConn.get(query, [name], (err, row) => {
            if (err) reject(err);
            resolve(row || { name: name, label: name, import_url: '' }); // Fallback básico
        });
    });
}

function getHoverEffectDetails(dbConn, name) {
    return new Promise((resolve, reject) => {
        const query = "SELECT name, label, css_code FROM custom_hover_effects WHERE name = ?";
        dbConn.get(query, [name], (err, row) => {
            if (err) reject(err);
            resolve(row || { name: name, label: name, css_code: '' }); // Fallback básico
        });
    });
}

// --- Rota Principal: GET /api/tenant/details ---
router.get('/details', async (req, res, next) => {
    if (!req.tenant) {
        return res.status(404).json({ error: 'Loja não identificada. Verifique o subdomínio.' });
    }

    let permDb = null;

    try {
        // 1. Abre a conexão com o banco de dados principal (permission.db)
        // Assumimos que tenant_themes e as tabelas custom_* estão todas lá.
        permDb = await connectPermissionDb();

        // 2. Busca os dados base do tema
        const themeBase = await new Promise((resolve, reject) => {
            const query = "SELECT * FROM tenant_themes WHERE tenant_id = ?";
            permDb.get(query, [req.tenant.id], (err, row) => {
                if (err) reject(err);
                resolve(row);
            });
        });

        if (!themeBase) {
            return res.status(404).json({ error: 'Configurações de tema da loja não encontradas.' });
        }

        // 3. Busca os DETALHES usando a mesma conexão (permDb)
        const [
            primaryColorDetail,
            secondaryColorDetail,
            fontDetail,
            hoverEffectDetail
        ] = await Promise.all([
            getColorDetails(permDb, themeBase.primary_color),
            getColorDetails(permDb, themeBase.secondary_color),
            getFontDetails(permDb, themeBase.font_ui),
            getHoverEffectDetails(permDb, themeBase.hover_effect)
        ]);

        // 4. Monta a resposta
        const responseData = {
            nome_loja: req.tenant.nome_loja,
            url_logo: req.tenant.url_logo,
            background_mode: themeBase.background_mode,
            has_box_shadow: themeBase.has_box_shadow,
            border_radius_px: themeBase.border_radius_px,
            primary_color: primaryColorDetail || { name: themeBase.primary_color, light: '#000000', dark: '#ffffff' },
            secondary_color: secondaryColorDetail || { name: themeBase.secondary_color, light: '#555555', dark: '#cccccc' },
            font_ui: fontDetail,
            hover_effect: hoverEffectDetail
        };
        console.log("Detalhes completos do tema buscados com sucesso para a loja:", req.tenant.url_logo);
        res.json(responseData);

    } catch (error) {
        console.error("Erro ao buscar detalhes completos do tema:", error);
        next(error);
    } finally {
        // 5. Garante que a conexão seja fechada
        closeDbConnection(permDb, 'permission.db');
    }
});

export default router;