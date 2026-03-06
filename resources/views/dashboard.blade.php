<!DOCTYPE html>
<html>

<head>

    <title>Crypto Watchlist</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>

</head>
<div id="loading" class="text-center my-3 d-none">
    <div class="spinner-border"></div>
</div>

<body class="bg-light">

    <div class="container mt-4">

        <h2 class="mb-4">Crypto Watchlist</h2>
        <h5 id="selectedCrypto" class="text-primary mb-3"></h5>

        <div class="mb-3">
            <button class="btn btn-sm btn-outline-primary range-btn" onclick="changeRange('24h')">24H</button>
            <button class="btn btn-sm btn-outline-primary range-btn" onclick="changeRange('7d')">7D</button>
            <button class="btn btn-sm btn-outline-primary range-btn" onclick="changeRange('30d')">30D</button>
        </div>
        <div class="card mb-4">
            <div class="card-body">
                <div class="mb-3">

                    <input id="searchCrypto" class="form-control" placeholder="Search crypto (ex: sol)"
                        onkeyup="searchCrypto()">

                    <div id="searchResults" class="list-group"></div>

                </div>
                <table class="table table-striped table-hover" id="table">

                    <thead class="table-dark">
                        <tr>
                            <th>Symbol</th>
                            <th>Name</th>
                            <th>Price</th>
                            <th>24h %</th>
                            <th>Market Cap</th>
                            <th>Volume</th>
                            {{-- <th style="width:120px">Trend</th> --}}
                            <th></th>
                        </tr>
                    </thead>

                    <tbody></tbody>

                </table>

                <div class="text-muted mb-2">
                    Última actualización: <span id="lastUpdate"></span>
                </div>

            </div>
        </div>

        <div class="card">
            <div class="card-body">

                <canvas id="chart" height="100"></canvas>

            </div>
        </div>

    </div>

</body>

</html>

<script>

    const API_BASE = '/crypto-watchlist/public/api/crypto'
    let chart;
    let selectedCrypto = null;

    function loadQuotes() {

        axios.get(API_BASE + '/quotes')
            .then(res => {

                let tbody = '';

                res.data.forEach((c, index) => {

                    let changeClass = c.change >= 0 ? 'text-success' : 'text-danger';

                    let selectedClass = c.id === selectedCrypto ? 'table-primary' : '';

                    tbody += `
                        <tr class="${selectedClass}" onclick="loadHistory(event, ${c.id}, '${c.symbol}')">
                            <td><strong>${c.symbol}</strong></td>
                            <td><strong>${c.name}</strong></td>
                            <td>${formatPrice(c.price)}</td>
                            <td class="${changeClass}">${c.change.toFixed(2)}%</td>
                            <td>$${formatLargeNumber(c.market_cap)}</td>
                            <td>$${formatLargeNumber(c.volume)}</td>
                            <td>
                                <button class="btn btn-sm btn-danger" onclick="deleteCrypto(event,${c.id})">
                                🗑
                                </button>
                            </td>
                        </tr>`;

                    if (index === 0 && !selectedCrypto) {
                        selectedCrypto = c.id;
                        selectedSymbol = c.symbol;
                    }

                });

                document.querySelector("#table tbody").innerHTML = tbody;
                // renderSparklines(res.data);
                document.getElementById("searchCrypto").value = "";
                document.getElementById("lastUpdate").innerText = new Date().toLocaleTimeString();
                // debugger;
                if (!chart) {
                    let firstRow = document.querySelector("#table tbody tr");
                    firstRow.classList.add("table-primary");
                    loadHistory(null, selectedCrypto, selectedSymbol);
                }
            });

    }

    function formatLargeNumber(num) {

        if (num >= 1e12) return (num / 1e12).toFixed(2) + 'T'
        if (num >= 1e9) return (num / 1e9).toFixed(2) + 'B'
        if (num >= 1e6) return (num / 1e6).toFixed(2) + 'M'

        return num
    }

    function renderSparklines(data) {

        data.forEach(c => {

            axios.get(API_BASE + '/history/' + c.id + '?range=24h')
                .then(res => {

                    let prices = res.data.map(p => p.price);

                    if (prices.length < 2) return;

                    let color = prices[prices.length - 1] >= prices[0]
                        ? '#198754'
                        : '#dc3545';

                    new Chart(document.getElementById('spark-' + c.id), {
                        type: 'line',
                        data: {
                            labels: prices.map(() => ''), // sin labels
                            datasets: [{
                                data: prices,
                                borderColor: color,
                                backgroundColor: color + '33',
                                borderWidth: 2,
                                pointRadius: 0,
                                tension: 0.3,
                                fill: true
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: { display: false },
                                tooltip: { enabled: false }
                            },
                            scales: {
                                x: { display: false },
                                y: { display: false }
                            }
                        }
                    });

                });

        });

    }

    function formatNumber(n) {
        return new Intl.NumberFormat('en-US').format(n);
    }

    function formatPrice(n) {
        return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' }).format(n);
    }

    function changeRange(range) {
        document.querySelectorAll(".range-btn").forEach(b => b.classList.remove("active"));
        if (!selectedCrypto) return;
        loadHistory(null, selectedCrypto, selectedSymbol, range);

        event.target.classList.add("active");
    }


    function loadHistory(event, id, symbol, range = '24h') {

        selectedCrypto = id;
        selectedSymbol = symbol;

        // limpiar selección previa
        if (event) {
            document.querySelectorAll("#table tbody tr")
                .forEach(r => r.classList.remove("table-primary"));

            // si viene desde click
            event.currentTarget.classList.add("table-primary");
        }

        document.getElementById("loading").classList.remove("d-none");

        axios.get(API_BASE + '/history/' + id + '?range=' + range)
            .then(res => {

                let labels = []
                let prices = []

                res.data.forEach(p => {
                    labels.push(p.source_timestamp)
                    prices.push(p.price)
                })

                drawChart(labels, prices, symbol)

                document.getElementById("loading").classList.add("d-none")

            })
    }

    function drawChart(labels, data, symbol) {

        let color = data[data.length - 1] >= data[0] ? '#198754' : '#dc3545';

        if (chart) {
            chart.destroy()
        }

        chart = new Chart(document.getElementById("chart"), {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Price ' + symbol,
                    data: data,
                    borderColor: color,
                    backgroundColor: color + '33',
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Price History - ' + symbol
                    }
                }
            }
        })
    }

    function searchCrypto(){

        let q=document.getElementById("searchCrypto").value

        if(q.length<2){
        document.getElementById("searchResults").innerHTML=''
        return
        }

        axios.get(API_BASE+'/search?q='+q)
        .then(res=>{

        let html=''

        res.data.forEach(c=>{

        html+=`
        <a class="list-group-item list-group-item-action"
        onclick="addCrypto(${c.id},'${c.symbol}','${c.name}')">

        ${c.symbol} - ${c.name}

        </a>
        `

        })

        document.getElementById("searchResults").innerHTML=html

        })

    }

    function addCrypto(id,symbol,name){

        axios.post(API_BASE+'/add',{
        cmc_id:id,
        symbol:symbol,
        name:name
        })
        .then(res=>{
            alert("Crypto added to watchlist");
            document.getElementById("searchCrypto").value=''
            document.getElementById("searchResults").innerHTML=''
            loadQuotes();
        })
        .catch(()=>{
            alert("Crypto already exists");
        })

    }

    function deleteCrypto(event,id){

        event.stopPropagation()

        if(!confirm("Delete this crypto?")) return

        axios.delete(API_BASE+'/'+id)
        .then(res=>{
            alert("Crypto deleted from watchlist");
            setTimeout(() => {
                if(id==selectedCrypto){
                    selectedCrypto=null;
                    document.getElementById("selectedCrypto").innerText=''
                    if(chart) chart.destroy();
                }
                loadQuotes();
            }, 500);
        })
        .catch(()=>{
            alert("Error deleting crypto");
        })
    }

    loadQuotes()

    setInterval(loadQuotes, 30000)

</script>