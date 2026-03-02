<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPT Rich Text</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .output {
            border: 1px solid #ccc;
            padding: 10px;
            margin-top: 10px;
            min-height: 100px;
        }
    </style>
</head>
<body>
    <h2>Generate Rich Text from GPT</h2>
    <button onclick="generate()">Generate</button>

    <div class="output" id="result"></div>

    <script>
        function generate() {
            $.ajax({
                url: "/generate",
                type: "POST",
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function(response) {
                    $("#result").html(response.content); // Renders rich text
                },
                error: function(xhr) {
                    $("#result").html("<p style='color:red;'>Error: " + xhr.responseText + "</p>");
                }
            });
        }
    </script>
</body>
</html>
