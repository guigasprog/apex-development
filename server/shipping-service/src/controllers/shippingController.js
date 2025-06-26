const { Produto } = require("../models/Produto");

exports.calculate = async (req, res) => {
  const { to_postal_code, products } = req.body;
  if (
    !to_postal_code ||
    !products ||
    !Array.isArray(products) ||
    products.length === 0
  ) {
    return res.status(400).json({
      error: "CEP de destino e um array de produtos são obrigatórios.",
    });
  }

  try {
    let totalWeightKg = 0;

    for (const item of products) {
      const productData = await Produto.findByPk(item.id);
      if (!productData) {
        return res
          .status(404)
          .json({ error: `Produto com ID ${item.id} não encontrado.` });
      }

      totalWeightKg += (productData.peso_kg || 0.3) * item.quantity;
    }

    const baseRate = 15.0;
    const ratePerKg = 5.0;

    const finalShippingCost = baseRate + Math.ceil(totalWeightKg) * ratePerKg;

    const sedexOption = {
      id: 2,
      name: "SEDEX",
      price: finalShippingCost.toFixed(2),
      delivery_time: 5,
      error: false,
    };

    return res.status(200).json([sedexOption]);
  } catch (error) {
    console.error("Controller Error:", error);
    res.status(500).json({
      error: "Erro interno ao calcular o frete.",
      details: error.message,
    });
  }
};
