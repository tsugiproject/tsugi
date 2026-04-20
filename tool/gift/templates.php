<script id="short_answer_question" type="text/x-handlebars-template">
  <li>
    <p id="{{code}}_q">
    {{#if scored}}{{#if correct}}
        <span class="sr-only">Correct. </span><i class="fa fa-check text-success" aria-hidden="true"></i>
    {{else}}
        <span class="sr-only">Incorrect. </span><i class="fa fa-times text-danger" aria-hidden="true"></i>
    {{/if}} {{/if}}
    {{{question}}}</p>
    <p><label for="{{code}}_input" class="sr-only">Your answer for question above</label><input type="text" id="{{code}}_input" name="{{code}}" value="{{value}}" size="80" aria-labelledby="{{code}}_q"/></p>
  </li>
</script>
<script id="essay_question" type="text/x-handlebars-template">
  <li>
    <p id="{{code}}_q">
    {{#if scored}}{{#if correct}}
        <span class="sr-only">Correct. </span><i class="fa fa-check text-success" aria-hidden="true"></i>
    {{else}}
        <span class="sr-only">Incorrect. </span><i class="fa fa-times text-danger" aria-hidden="true"></i>
    {{/if}} {{/if}}
    {{{question}}}</p>
    <p><label for="{{code}}_input" class="sr-only">Your answer for question above</label><textarea id="{{code}}_input" name="{{code}}" style="width:100%;" aria-labelledby="{{code}}_q">{{value}}</textarea></p>
  </li>
</script>
<script id="multiple_answers_question" type="text/x-handlebars-template">
  <li>
    <p id="{{code}}_q">
    {{#if scored}}{{#if correct}}
        <span class="sr-only">Correct. </span><i class="fa fa-check text-success" aria-hidden="true"></i>
    {{else}}
        <span class="sr-only">Incorrect. </span><i class="fa fa-times text-danger" aria-hidden="true"></i>
    {{/if}} {{/if}}
    {{{question}}}</p>
    <div role="group" aria-labelledby="{{code}}_q">
    {{#each answers}}
    <p><input type="checkbox" id="{{../code}}_{{@index}}" name="{{code}}" {{#if checked}}checked{{/if}} value="true"/> <label for="{{../code}}_{{@index}}">{{text}}</label></p>
    {{/each}}
    </div>
  </li>
</script>
<script id="true_false_question" type="text/x-handlebars-template">
  <li>
    <p id="{{code}}_q">
    {{#if scored}}{{#if correct}}
        <span class="sr-only">Correct. </span><i class="fa fa-check text-success" aria-hidden="true"></i>
    {{else}}
        <span class="sr-only">Incorrect. </span><i class="fa fa-times text-danger" aria-hidden="true"></i>
    {{/if}} {{/if}}
    {{{question}}}</p>
    <p role="group" aria-labelledby="{{code}}_q">
    <input type="radio" id="{{code}}_T" name="{{code}}" {{#if value_true}}checked{{/if}} value="T"/> <label for="{{code}}_T">True</label>
    <input type="radio" id="{{code}}_F" name="{{code}}" {{#if value_false}}checked{{/if}} value="F"/> <label for="{{code}}_F">False</label>
    </p>
  </li>
</script>
<script id="multiple_choice_question" type="text/x-handlebars-template">
  <li>
    <p id="{{code}}_q">
    {{#if scored}}{{#if correct}}
        <span class="sr-only">Correct. </span><i class="fa fa-check text-success" aria-hidden="true"></i>
    {{else}}
        <span class="sr-only">Incorrect. </span><i class="fa fa-times text-danger" aria-hidden="true"></i>
    {{/if}} {{/if}}
    {{{question}}}</p>
    <div role="group" aria-labelledby="{{code}}_q">
    {{#each answers}}
    <p><input type="radio" id="{{../code}}_{{@index}}" name="{{../code}}" {{#if checked}}checked{{/if}} value="{{code}}"/> <label for="{{../code}}_{{@index}}">{{text}}</label></p>
    {{/each}}
    </div>
  </li>
</script>
