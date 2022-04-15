
new Vue({
  el: '#area',
  data() {
    return {
      val: 'area-input-select',
      isActiveManual: false,
      isActiveSelect: true
    }
  },
  methods: {
    toggleForm: function (e) {
        this.val = e.target.value
        if(this.val == "area-input-manual")
        {
          this.isActiveManual = true
          this.isActiveSelect = false
        }
        if(this.val == "area-input-select")
        {
          this.isActiveManual = false
          this.isActiveSelect = true

          $(function() {
            let initPrefVal = '都道府県を選択'; //selectedを付与したい都道府県
            let initCityVal = '市区町村を選択'; //selectedを付与したい市区町村
            let valType = 'name';  // 'name' or 'code'
    
            // 都道府県リストを作成
            getPrefectureSelection('#area_select_prefecture', '#area_select_city', 'https://weather-info-ss.com/climate/public/json/prefectureCity.json', valType, initPrefVal, initCityVal);
    
            // 都道府県選択時に市区町村リストを作成
            $('#area_select_prefecture').on('change', function() {
              getCitySelection('#area_select_prefecture', '#area_select_city', 'https://weather-info-ss.com/climate/public/json/prefectureCity.json', valType);
            });
          });
        }
    }
  }
})


var precipitation_list = []
var n_precipitation_list = 6290
var precipitation_interval = 10
for(var i=0;i<=n_precipitation_list/precipitation_interval;i++){
  precipitation_list.push(i * precipitation_interval)
}
new Vue({
  el: '#precipitation',
  data: {
      options: precipitation_list
  }
});

var wind_speed_list = []
var n_wind_speed_list = 80
var wind_speed_interval = 10
for(var i=0;i<=n_wind_speed_list/wind_speed_interval;i++){
  wind_speed_list.push(i * wind_speed_interval)
}
new Vue({
  el: '#wind_speed',
  data: {
      options: wind_speed_list
  }
});

var wind_direction_list = ["指定なし","北","北北東","北東", "東北東", "東", "東南東", "南東", "南南東", "南", "南南西", "南西", "西南西", "西", "西北西", "北西", "北北西"];
new Vue({
  el: '#wind_direction',
  data: {
      options: wind_direction_list
  }
});

// google maps api

let map;
let mainMarker;
let marker =[];
let infoWindow = [];

// 検索（非同期通信）
function getData() {

  // メッセージ初期化
  document.getElementById("loading").textContent = "検索中です..."
  document.getElementById("loading").style.margin = "1rem 0 0 0"
  document.getElementById("liden-result-msg").textContent = ""
  document.getElementById("thander-before-liden-result-msg").textContent = ""
  document.getElementById("amedas-result-msg").textContent = ""
  document.getElementById("map-title").textContent = ""
  document.getElementById("btn-create-img").style.display = "none"

  // マップ初期化
  document.getElementById("map").style.width = "0"
  document.getElementById("map").style.height = "0"
  if(document.getElementById("map").childElementCount > 0)
  {
    document.getElementById("map").innerHTML = ""
  }

  // テーブル初期化
  if(document.getElementById("climate-table").childElementCount > 0)
  {
    document.getElementById("climate-table").innerHTML = ""
  }
  
  if(document.getElementById("area_select_prefecture"))
  {
    var area = document.getElementById("area_select_prefecture").value + document.getElementById("area_select_city").value
  }
  else if(document.getElementById("area_manual"))
  {
    var area = document.getElementById("area_manual").value
  }
  console.log(area)
  var data = {
    "area": area,
    "start_date": document.getElementById("start_date").value,
    "end_date": document.getElementById("end_date").value,
    "thander": document.getElementById("thander_select").value,
    "precipitation": document.getElementById("precipitation_select").value,
    "wind_speed": document.getElementById("wind_speed_select").value,
    "wind_direction": document.getElementById("wind_direction_select").value
  }
  
  if(data.area == "")
  {
    document.getElementById("loading").textContent = "地点を入力してください"
    console.log(false_data)
  }

  if(data.start_date == "")
  {
    document.getElementById("loading").textContent = "期間を入力してください"
    console.log(false_data)
  }

  if(data.end_date == "")
  {
    document.getElementById("loading").textContent = "期間を入力してください"
    console.log(false_data)
  }

  var json = JSON.stringify(data)
  var xhr = new XMLHttpRequest()
  console.log(location.href+"/api")
  xhr.open("POST", location.href+"/api")
  xhr.setRequestHeader("content-type", "application/x-www-form-urlencoded;charset=UTF-8")
  xhr.send(json);

  xhr.onreadystatechange = function () {
    try 
    {
      if (xhr.readyState == 4) 
      {
        console.log("state 4.")
        if (xhr.status == 200) 
        {
          console.log("200 ok.")

          console.log(xhr.response);
          var result = JSON.parse(xhr.response);
          console.log("result:")
          console.log(result);

          document.getElementById("loading").textContent = ""
          document.getElementById("loading").style.margin = 0
          document.getElementById("btn-create-img").style.display = "block"

          // 地図出力
          if(result.liden_data_array != "no_thander")
          {
            // 落雷マップ描画 & 件数メッセージ
            if(result.liden_data_array.length != 0)
            {
              console.log("ライデンデータあり")
              document.getElementById("liden-result-msg").textContent = "落雷データ(2020年10月3日以降)："+result.liden_data_array.length+"件"
              document.getElementById("map").style.width = "800px"
              document.getElementById("map").style.height = "800px"
              if(new Date(data.start_date) < new Date("2020-10-03"))
              {
                liden_start_date = "2020-10-03"
              }
              else{
                liden_start_date = data.start_date;
              }
              document.getElementById("map-title").textContent = data.area+"の"+liden_start_date+"～"+data.end_date+"の落雷地点"
              var latlng = new google.maps.LatLng(result.center_lat, result.center_lon);
              var opts = {
                zoom: 13,
                center: latlng,
                zoomControl: false,
                mapTypeControl: false,
                fullscreenControl: false,
              };
              var map = new google.maps.Map(document.getElementById("map"), opts);
  
              /*========= monochrome =========*/
              var mapStyle = [ {
                "stylers": [ {
                "saturation": -100
                  } ]
              } ];
              var mapType = new google.maps.StyledMapType(mapStyle);
              map.mapTypes.set( 'GrayScaleMap', mapType);
              map.setMapTypeId( 'GrayScaleMap' );
              /*========= monochrome =========*/
  
              var window_date_array = [];
              for (var i = 0; i < result.liden_data_array.length; i++) 
              {
                const id = result.liden_data_array[i].id;
                // 緯度経度のデータを作成
                let markerLatLng = new google.maps.LatLng({lat: parseFloat(result.liden_data_array[i].lat), lng: parseFloat(result.liden_data_array[i].lon) });
                // マーカーの追加
                marker[i] = new google.maps.Marker({
                  position: markerLatLng,
                  map: map,
                });
            
                // 吹き出しの追加
                infoWindow[i] = new google.maps.InfoWindow({
                  content: "<div>観測日:"+result.liden_data_array[i].date+"</div><div>北緯:"+result.liden_data_array[i].lat+"</div><div>東経:"+result.liden_data_array[i].lon+"</div>"
                });
            
                if(window_date_array.indexOf(result.liden_data_array[i].date) == -1)
                {
                  window_date_array.push(result.liden_data_array[i].date);
                  infoWindow[i].open(map, marker[i]);
                }
                
                markerEvent(i);
                function markerEvent(i) {
                  marker[i].addListener('click', function() {
                    infoWindow[i].open(map, marker[i]);
                  });
                }
              }
            }
          }
            
          // アメダスデータ表作成 & 件数メッセージ　& 2020/9/3以前の落雷件数表示
          var thander_before_liden = 0;
          if(result.amedas_data_array.length > 0)
          {
            console.log("アメダスデータあり")
            document.getElementById("amedas-result-msg").textContent = "アメダスデータ："+result.amedas_data_array.length+"件"
            page = 0
            for (var i = 0; i < result.amedas_data_array.length; i++) 
            {
              // 20の倍数で改ページ
              if(i % 20 == 0)
              {
                page++;
                var tableParent = document.createElement('div');
                tableParent.style.backgroundColor = "white";
                tableParent.id = "table-parent"+String(page)
                document.getElementById("climate-table").appendChild(tableParent)
  
                var climateTableTitle = document.createElement("div")
                climateTableTitle.id = "climate-table-title"+String(page)
                climateTableTitle.textContent = data.area+"の"+data.start_date+"～"+data.end_date+"の天気情報(観測所："+result.st_name+")"
                climateTableTitle.style.fontSize = "1rem"
                climateTableTitle.style.textAlign = "center"
                climateTableTitle.style.fontWeight = "900"
                tableParent.appendChild(climateTableTitle)
  
                var table = document.createElement('table');
                var thead = document.createElement('thead');
                var tbody = document.createElement('tbody');
                table.appendChild(thead);
                table.appendChild(tbody);
                
                tableParent.appendChild(table);
                var row_1 = document.createElement('tr');
                var heading_1 = document.createElement('th');
                heading_1.innerHTML = "日付";
                var heading_2 = document.createElement('th');
                heading_2.innerHTML = "降水量(mm)";
                var heading_3 = document.createElement('th');
                heading_3.innerHTML = "最大瞬間風速(m/s)";
                var heading_4 = document.createElement('th');
                heading_4.innerHTML = "最大瞬間風速風向";
                if(data.thander != "指定なし")
                {
                  var heading_5 = document.createElement('th');
                  heading_5.innerHTML = "落雷";
                }
                row_1.appendChild(heading_1);
                row_1.appendChild(heading_2);
                row_1.appendChild(heading_3);
                row_1.appendChild(heading_4);
                if(data.thander != "指定なし")
                {
                  row_1.appendChild(heading_5);
                }
                thead.appendChild(row_1);
              }
              var row_2 = document.createElement('tr');
              var row_2_data_1 = document.createElement('td');
              row_2.appendChild(row_2_data_1);
  
              row_2_data_1.innerHTML = result.amedas_data_array[i].date;
              var row_2_data_2 = document.createElement('td');
              row_2.appendChild(row_2_data_2);
  
              row_2_data_2.innerHTML = result.amedas_data_array[i].pricipitation;
              var row_2_data_3 = document.createElement('td');
              row_2.appendChild(row_2_data_3);
  
              row_2_data_3.innerHTML = result.amedas_data_array[i].wind_speed;
              var row_2_data_4 = document.createElement('td');
              row_2.appendChild(row_2_data_4);
  
              row_2_data_4.innerHTML = result.amedas_data_array[i].wind_direction;
              if(data.thander != "指定なし")
              {
                var row_2_data_5 = document.createElement('td');
                if(data.thander == "あり")
                {
                  if(result.amedas_data_array[i].date <="2020-10-02")
                  {
                    result_thander = "県内であり"
                  }
                  else{
                    result_thander = "あり"
                  }
                }
                if(data.thander == "なし")
                {
                  if(result.amedas_data_array[i].date <="2020-10-02")
                  {
                    result_thander = "県内で無し"
                  }
                  else{
                    result_thander = "無し"
                  }
                }
                
                row_2_data_5.innerHTML = result_thander;
                row_2.appendChild(row_2_data_5);
              }
             
              tbody.appendChild(row_2);

              if(new Date(result.amedas_data_array[i].date) < new Date("2020-10-03"))
              {
                thander_before_liden += 1;
              }
            }
            document.getElementById("thander-before-liden-result-msg").textContent = "落雷データ(2020年10月2日以前)："+thander_before_liden+"件"
          }
          else{
            document.getElementById("loading").textContent = "該当データはありません"
          }
          console.log("try success.");
        } 
        else 
        {
          console.log("state 4 but error.")
          document.getElementById("loading").textContent = "レスポンスエラー"
        }
      } 
      else 
      {
        console.log("failed.")
        document.getElementById("loading").textContent = "リクエストエラー"
      }
    } 
    catch (e) 
    {
      console.log("catch.")
      document.getElementById("loading").textContent = "サーバエラー"
    }
  };
}

var btn = document.getElementById("btn")
btn.addEventListener("click",() => {

  // マップ画像化
  if(document.getElementById('map').textContent != "")
  {
    html2canvas(document.querySelector("#map-parent"), { 
      Proxy: true,
      useCORS: true,
      onrendered: function(canvas)
      {
        var downloadEle = document.createElement("a")
        downloadEle.href = canvas.toDataURL("image/jpg")
        downloadEle.download = "map.jpg"
        downloadEle.click()
      }
    });
  }
  
  
  // テーブル画像化
  for(p=0; p<page; p++)
  {
    downloadEle = []
    html2canvas(document.querySelector("#table-parent"+String(p+1)), {
      onrendered: function(canvas) {
        downloadEle[p] = document.createElement("a")
        downloadEle[p].href = canvas.toDataURL("image/jpg")
        downloadEle[p].download = "table.jpg"
        downloadEle[p].click()
      }
    })
  }
})

function sleep(waitMsec) {
  var startMsec = new Date();
 
  // 指定ミリ秒間だけループさせる（CPUは常にビジー状態）
  while (new Date() - startMsec < waitMsec);
}
 
