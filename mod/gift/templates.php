<script id="short_answer_question" type="text/x-handlebars-template">
  <li>
    <p>{{{question}}}</p>
    <p><input type="text" name="{{code}}" size="80"/></p>
  </li>
</script>
<script id="multiple_answers_question" type="text/x-handlebars-template">
  <li>
    <p>{{{question}}}</p>
    <div>
    {{#each answers}}
    <p><input type="checkbox" name="{{code}}" value="true"/> {{text}}</p>
    {{/each}}
    </div>
  </li>
</script>
<script id="true_false_question" type="text/x-handlebars-template">
  <li>
    <p>{{{question}}}</p>
    <p><input type="radio" name="{{code}}" value="T"/> True
    <input type="radio" name="{{code}}" value="F"/> False
    </p>
  </li>
</script>
<script id="multiple_choice_question" type="text/x-handlebars-template">
  <li>
    <p>{{{question}}}</p>
    <div>
    {{#each answers}}
    <p><input type="radio" name="{{code}}" value="true"/> {{text}}</p>
    {{/each}}
    </div>
  </li>
</script>
