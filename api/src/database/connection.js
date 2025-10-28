// src/database/connection.js
import sqlite3 from 'sqlite3';
import dotenv from 'dotenv';
import path from 'path'; // Para construir caminhos

dotenv.config();

const permissionDbPath = process.env.PERMISSION_DB_PATH;
const tenantDbFolder = process.env.TENANT_DB_FOLDER;

if (!permissionDbPath || !tenantDbFolder) {
    throw new Error("Caminhos PERMISSION_DB_PATH ou TENANT_DB_FOLDER não definidos no .env");
}

/**
 * Cria uma conexão com o banco de dados principal (permission).
 * Retorna uma Promise que resolve com o objeto de conexão.
 */
export function connectPermissionDb(readOnly = true) {
    return new Promise((resolve, reject) => {
        const mode = readOnly ? sqlite3.OPEN_READONLY : sqlite3.OPEN_READWRITE;
        const db = new sqlite3.Database(permissionDbPath, mode, (err) => {
            if (err) {
                console.error('Erro ao conectar ao permission.db:', err.message);
                reject(err);
            } else {
                console.log('Conectado ao permission.db.');
                resolve(db);
            }
        });
    });
}

/**
 * Cria uma conexão com o banco de dados específico de um tenant.
 * @param {number} tenantId O ID do tenant.
 * @returns {Promise<sqlite3.Database>} Promise que resolve com a conexão do tenant.
 */
export function connectTenantDb(tenantId) {
    return new Promise((resolve, reject) => {
        const tenantDbName = `tenant_${tenantId}.db`;
        const tenantDbPath = path.join(tenantDbFolder, tenantDbName);

        // Importante: Assume que o banco do tenant precisa de escrita (para futuras operações)
        const db = new sqlite3.Database(tenantDbPath, sqlite3.OPEN_READWRITE | sqlite3.OPEN_CREATE, (err) => {
            if (err) {
                console.error(`Erro ao conectar ao ${tenantDbName}:`, err.message);
                reject(err);
            } else {
                console.log(`Conectado ao ${tenantDbName}.`);
                // Habilitar chaves estrangeiras (importante para o ON DELETE CASCADE)
                db.run('PRAGMA foreign_keys = ON;', pragmaErr => {
                    if (pragmaErr) {
                         console.error(`Erro ao habilitar foreign keys para ${tenantDbName}:`, pragmaErr.message);
                         reject(pragmaErr);
                    } else {
                        resolve(db);
                    }
                });
            }
        });
    });
}

/**
 * Função utilitária para fechar uma conexão de banco de dados de forma segura.
 * @param {sqlite3.Database} dbConnection A conexão a ser fechada.
 * @param {string} dbName Nome do banco (para logging).
 */
export function closeDbConnection(dbConnection, dbName = 'DB') {
    if (dbConnection) {
        dbConnection.close((err) => {
            if (err) {
                console.error(`Erro ao fechar conexão com ${dbName}:`, err.message);
            } else {
                console.log(`Conexão com ${dbName} fechada.`);
            }
        });
    }
}