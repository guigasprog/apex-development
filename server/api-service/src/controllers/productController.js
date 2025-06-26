const { Produto, Categoria, Estoque, ImagemProduto } = require("../models");

exports.getAllProducts = async (req, res) => {
  try {
    const products = await Produto.findAll({
      include: [
        { model: Categoria, as: "categoria" },
        { model: Estoque, as: "estoques" },
        {
          model: ImagemProduto,
          as: "imagens",
          attributes: ["descricao", "image_url"],
        },
      ],
    });
    res.status(200).json(products);
  } catch (error) {
    res
      .status(500)
      .json({ error: "Erro ao buscar produtos.", details: error.message });
  }
};

exports.getProductById = async (req, res) => {
  try {
    const { id } = req.params;

    const product = await Produto.findByPk(id, {
      include: [
        { model: Categoria, as: "categoria" },
        { model: Estoque, as: "estoques" },
        { model: ImagemProduto, as: "imagens" },
      ],
    });

    if (!product) {
      return res.status(404).json({ error: "Produto não encontrado." });
    }

    res.status(200).json(product);
  } catch (error) {
    res
      .status(500)
      .json({ error: "Erro ao buscar o produto.", details: error.message });
  }
};
