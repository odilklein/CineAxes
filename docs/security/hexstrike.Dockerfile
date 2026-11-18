# HexStrike AI em container isolado (Kali), com as ferramentas que o servidor
# orquestra. Roda com --network host para alcançar o site local (localhost:8090)
# e expor a API em 127.0.0.1:8888 para o cliente MCP do OpenCode.
FROM kalilinux/kali-last-release

ENV DEBIAN_FRONTEND=noninteractive \
    PYTHONUNBUFFERED=1

RUN apt-get update && apt-get install -y --no-install-recommends \
        python3 python3-venv python3-pip python3-dev \
        build-essential libffi-dev libssl-dev zlib1g-dev \
        curl ca-certificates \
        nmap nikto sqlmap gobuster ffuf whatweb wafw00f dirb dnsutils \
    && rm -rf /var/lib/apt/lists/*

# nuclei nem sempre está no snapshot do repositório; não falha o build por isso.
RUN apt-get update && (apt-get install -y --no-install-recommends nuclei || echo "nuclei indisponivel no repositorio") \
    && rm -rf /var/lib/apt/lists/*

RUN python3 -m venv /opt/venv \
    && /opt/venv/bin/pip install --no-cache-dir --upgrade pip \
    && /opt/venv/bin/pip install --no-cache-dir \
        "flask>=2.3,<4" \
        "requests>=2.31,<3" \
        "psutil>=5.9,<6" \
        "aiohttp>=3.8,<4" \
        "beautifulsoup4>=4.12,<5" \
        "selenium>=4.15,<5" \
        "webdriver-manager>=4,<5" \
        "mitmproxy>=9,<11" \
        "mcp<2"

WORKDIR /opt/hexstrike
COPY hexstrike_server.py .

EXPOSE 8888
CMD ["/opt/venv/bin/python", "hexstrike_server.py", "--port", "8888"]
