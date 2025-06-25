const { Pedido, PedidoProduto, Produto, sequelize } = require('../models');

exports.createOrder = async (req, res) => {
    const t = await sequelize.transaction();

    try {
        const cliente_id = req.userId;
        const { items } = req.body;

        if (!items || items.length === 0) {
            await t.rollback();
            return res.status(400).json({ error: 'O pedido precisa conter pelo menos um item.' });
        }

        let totalPedido = 0;
        for (const item of items) {
            const produto = await Produto.findByPk(item.produto_id, { transaction: t });
            if (!produto) {
                throw new Error(`Produto com ID ${item.produto_id} não encontrado.`);
            }
            totalPedido += produto.preco * item.quantidade;
        }

        const novoPedido = await Pedido.create({
            cliente_id: cliente_id,
            total: totalPedido,
            status: 'pendente'
        }, { transaction: t });

        for (const item of items) {
            await PedidoProduto.create({
                pedido_id: novoPedido.id,
                produto_id: item.produto_id,
                quantidade: item.quantidade
            }, { transaction: t });
        }

        await t.commit();

        res.status(201).json({ message: 'Pedido criado com sucesso!', pedidoId: novoPedido.id, total: totalPedido });

    } catch (error) {
        await t.rollback();
        console.error(error);
        res.status(500).json({ error: 'Erro ao criar o pedido.', details: error.message });
    }
};