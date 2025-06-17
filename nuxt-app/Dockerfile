FROM node:18-alpine

WORKDIR /app

# パッケージファイルをコピー
COPY package*.json ./

# 依存関係をインストール
RUN npm install

# アプリケーションファイルをコピー
COPY . .

# ポート3000を公開
EXPOSE 3000

# 開発サーバーを起動（ホスト0.0.0.0で起動）
CMD ["npm", "run", "dev", "--", "--host", "0.0.0.0"]