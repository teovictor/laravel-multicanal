<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Criar produto</title>
    <style>
        body {
            background: #f6f7f9;
            color: #1f2933;
            font-family: Arial, sans-serif;
            line-height: 1.5;
            margin: 0;
        }

        main {
            margin: 0 auto;
            max-width: 720px;
            padding: 40px 20px;
        }

        h1 {
            font-size: 28px;
            margin: 0 0 24px;
        }

        form {
            background: #ffffff;
            border: 1px solid #d8dee6;
            border-radius: 6px;
            padding: 24px;
        }

        label {
            display: block;
            font-weight: 700;
            margin-bottom: 6px;
        }

        input[type="text"],
        input[type="number"],
        select,
        textarea {
            border: 1px solid #aeb7c2;
            border-radius: 4px;
            box-sizing: border-box;
            font: inherit;
            padding: 10px 12px;
            width: 100%;
        }

        textarea {
            min-height: 120px;
            resize: vertical;
        }

        .field {
            margin-bottom: 18px;
        }

        .checkbox-field {
            align-items: center;
            display: flex;
            gap: 8px;
            margin-bottom: 18px;
        }

        .checkbox-field label {
            font-weight: 400;
            margin: 0;
        }

        .error {
            color: #b42318;
            margin: 6px 0 0;
        }

        .alert {
            border-radius: 4px;
            margin-bottom: 18px;
            padding: 12px 14px;
        }

        .alert-success {
            background: #e7f6ec;
            border: 1px solid #9bd4ad;
            color: #14532d;
        }

        .alert-error {
            background: #fdecec;
            border: 1px solid #f5b5b5;
            color: #7f1d1d;
        }

        button {
            background: #1f6feb;
            border: 0;
            border-radius: 4px;
            color: #ffffff;
            cursor: pointer;
            font: inherit;
            font-weight: 700;
            padding: 10px 16px;
        }
    </style>
</head>
<body>
    <main>
        <h1>Criar produto</h1>

        @if (session('status'))
            <div class="alert alert-success" role="status">
                {{ session('status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-error" role="alert">
                Revise os campos destacados e tente novamente.
            </div>
        @endif

        <form method="POST" action="{{ route('products.store') }}">
            @csrf

            <div class="field">
                <label for="category_id">Categoria</label>
                <select id="category_id" name="category_id" required>
                    <option value="">Selecione uma categoria</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected((string) old('category_id') === (string) $category->id)>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
                @error('category_id')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="name">Nome</label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name') }}"
                    maxlength="255"
                    required
                >
                @error('name')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="description">Descrição</label>
                <textarea id="description" name="description">{{ old('description') }}</textarea>
                @error('description')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="sku">SKU</label>
                <input
                    id="sku"
                    name="sku"
                    type="text"
                    value="{{ old('sku') }}"
                    maxlength="255"
                    required
                >
                @error('sku')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="price">Preço</label>
                <input
                    id="price"
                    name="price"
                    type="number"
                    value="{{ old('price') }}"
                    step="0.01"
                    min="0"
                    required
                >
                @error('price')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>

            <div class="field">
                <label for="stock">Estoque</label>
                <input
                    id="stock"
                    name="stock"
                    type="number"
                    value="{{ old('stock') }}"
                    min="0"
                    required
                >
                @error('stock')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>

            <input type="hidden" name="is_active" value="0">
            <div class="checkbox-field">
                <input
                    id="is_active"
                    name="is_active"
                    type="checkbox"
                    value="1"
                    @checked(old('is_active', '1') === '1')
                >
                <label for="is_active">Produto ativo</label>
                @error('is_active')
                    <p class="error">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit">Criar produto</button>
        </form>
    </main>
</body>
</html>
